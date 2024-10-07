<?php

namespace App\Utils;

use App\Entity\User;
use Swoole\Coroutine;
use Swoole\WebSocket\Server;

class SocketUtil {
    const CTX_SERVER = 'server';
    const CTX_FD = 'fd';
    const CTX_UID = 'uid';
    const CTX_USER = 'user';
    const CTX_REQUEST = 'request';
    
    public static function contextGet(string $key, $default = null) {
        return Coroutine::getContext()[$key] ?? $default;
    }
    public static function contextSet(string $key, $value) {
        $context = Coroutine::getContext();
        if (!is_null($context)) $context[$key] = $value;
    }
    public static function contextServer(): Server {
        $server = self::contextGet(self::CTX_SERVER);
        if (is_null($server)) $server = $GLOBALS['server'] ?? null;
        return $server;
    }
    public static function contextFd(): int {
        return self::contextGet(self::CTX_FD);
    }
    public static function contextUid(): string {
        return self::contextGet(self::CTX_UID);
    }
    public static function contextUser(): ?User {
        return self::contextGet(self::CTX_USER);
    }
    public static function contextRequest(): array {
        return self::contextGet(self::CTX_REQUEST, []);
    }
    public static function contextRequestId(): ?string {
        return self::contextRequest()['requestId'] ?? null;
    }




    public static function pushGroup(array $fds, string $action, array $data, ?Server $server = null) {
        if (empty($fds)) return;
        $raw = [
            'action' => $action,
            'data' => $data
        ];
        if (is_null($server)) $server = self::contextServer();
        if (!is_null($server)) {
            foreach ($fds as $fd) {
                $server->push($fd, json_encode($raw, JSON_UNESCAPED_UNICODE));
            }
        }
    }
    public static function push(int $fd, string $action, array $data, ?Server $server = null) {
        $raw = [
            'action' => $action,
            'data' => $data
        ];
        if (is_null($server)) $server = self::contextServer();
        if (!is_null($server)) $server->push($fd, json_encode($raw, JSON_UNESCAPED_UNICODE));
    }
    public static function pushSuccess() {
        self::push(SocketUtil::contextFd(), 'success', [
            'requestId' => SocketUtil::contextRequestId()
        ]);
    }
    public static function pushSuccessWithData(array $data) {
        $data['requestId'] = SocketUtil::contextRequestId();
        self::push(SocketUtil::contextFd(), 'success', $data);
    }
    public static function pushResponse(int $fd, array $request, string $action, array $data) {
        $data['requestId'] = $request['requestId'] ?? null;
        self::push($fd, $action, $data);
    }
    public static function push403() {
        self::push(SocketUtil::contextFd(), '403', [
            'requestId' => SocketUtil::contextRequestId()
        ]);
    }
    public static function pushError(string $errorMsg = '请求错误') {
        self::push(SocketUtil::contextFd(), 'error', [
            'requestId' => SocketUtil::contextRequestId(),
            'elementMessage' => [
                'type' => 'error',
                'message' => $errorMsg
            ]
        ]);
    }
}