<?php

namespace App\Service;

use App\Exception\LoginException;
use App\Utils\FdControl;
use App\Utils\SocketUtil;
use Swoole\WebSocket\Frame;

class SocketService {
    static $nameDictTest = [];
    
    const NO_AUTH_LIST = [
        'login',
        'ping'
    ];
    public function onMessage(Frame $frame, string $fd) {
        $raw = json_decode($frame->data, true);
        if (empty($raw)) return;
        SocketUtil::contextSet(SocketUtil::CTX_REQUEST, $raw);
        $action = $raw['action'] ?? null;
        if (empty($action)) return;
        $data = $raw['data'] ?? [];
        
        if (!in_array($action, self::NO_AUTH_LIST)) {
            //需校验的接口
            $uid = FdControl::fd2Uid($fd);
            if (is_null($uid)) return;
            SocketUtil::contextSet(SocketUtil::CTX_UID, $uid);
        }
        
        $actionMethod = 'action' . ucfirst($action);
        if (method_exists($this, $actionMethod)) {
            $this->$actionMethod($data);
        }
        
        //更新updated_at和ping
    }
    public function actionPing(array $data) {
        $this->log('ping');
        SocketUtil::pushSuccess();
    }
    public function actionLogin(array $data) {
        $this->log('login');
        $uid = $data['uid'];
        $name = $data['name'];
        if (strlen($uid) !== 8) {
            SocketUtil::pushError('uid错误');
            return;
        }
        if (strlen($name) > 32) {
            SocketUtil::pushError('姓名过长');
            return;
        }
        $user = FdControl::login(strval(SocketUtil::contextFd()), $uid, $name);
        SocketUtil::pushSuccess();
    }
    
    
    
    
    
    
    
    
    public function onFdClose(string $fd) {
        
    }
    public function log(string $content) {
        echo $content;
    }
}