<?php

namespace App\Utils;

use Swoole\Coroutine;
use Swoole\WebSocket\Server;

class SocketUtil {
    const CTX_SERVER = 'server';
    const CTX_FD = 'fd';
    const CTX_UID = 'uid';
    const CTX_REQUEST = 'request';
    
    public static function contextGet(string $key, $default = null) {
        return Coroutine::getContext()[$key] ?? $default;
    }
    public static function contextSet(string $key, $value) {
        $context = Coroutine::getContext();
        if (!is_null($context)) $context[$key] = $value;
    }
    public static function contextServer(): Server {
        return self::contextGet(self::CTX_SERVER);
    }
    public static function contextFd(): int {
        return self::contextGet(self::CTX_FD);
    }
    public static function contextUid(): string {
        return self::contextGet(self::CTX_UID);
    }
    public static function contextRequest(): array {
        return self::contextGet(self::CTX_REQUEST);
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
        $request = SocketUtil::contextRequest();
        self::push(SocketUtil::contextFd(), 'Success', [
            'requestId' => $request['requestId'] ?? null
        ]);
    }
    public static function pushResponse(int $fd, array $request, string $action, array $data) {
        $data['requestId'] = $request['requestId'] ?? null;
        self::push($fd, $action, $data);
    }
    public static function pushError(string $errorMsg = '请求错误') {
        $request = SocketUtil::contextRequest();
        self::push(SocketUtil::contextFd(), 'Error', [
            'requestId' => $request['requestId'] ?? null,
            'elementMessage' => [
                'type' => 'error',
                'message' => $errorMsg
            ]
        ]);
    }
}