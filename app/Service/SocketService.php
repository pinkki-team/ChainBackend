<?php

namespace App\Service;

use App\Exception\LoginException;
use App\Utils\FdControl;
use App\Utils\SocketUtil;
use Swoole\WebSocket\Frame;

class SocketService {
    static $nameDictTest = [];
    public function onMessage(Frame $frame, string $fd) {
        $data = json_decode($frame->data, true);
        if (empty($data)) return;
        SocketUtil::contextSet(SocketUtil::CTX_REQUEST, $data);
        $type = $data['type'] ?? null;
        if (empty($type)) return;
        
        if ($type === 'login') {
            //无需校验的接口
            $this->actionLogin($data);
            return;
        }
        
        $uid = FdControl::fd2Uid($fd);
        if (is_null($uid)) return;
        SocketUtil::contextSet(SocketUtil::CTX_UID, $uid);
        
        
        $actionMethod = 'action' . ucfirst($type);
        if (method_exists($this, $actionMethod)) {
            $this->$actionMethod($data);
        }
        
        //更新updated_at
    }
    
    public function actionLogin(array $data) {
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