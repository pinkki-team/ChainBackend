<?php

namespace App\Utils;

use App\Entity\User;
use App\Exception\BadFDException;
use App\Exception\LoginException;
use Swoole\Table;

class FdControl {
    public static function fd2Uid(string $fd): ?string {
        //先找fd
        $fdTable = TableUtil::fdTable();
        $uid = $fdTable->get($fd, 'uid');
        if ($uid === false) {
            return null;
        }
        
        return $uid;
    }
    /**  @throws BadFDException */
    public static function fd2User(string $fd): User {
        //先找fd
        $uid = self::fd2Uid($fd);
        if (is_null($uid)) {
            throw new BadFDException();
        }
        //找uid
        $userTable = TableUtil::userTable();
        $user = new User();
        $findUserModel = $userTable->get($uid);
        if ($findUserModel === false) {
            throw new BadFDException();
        }
        $user->uid = $uid;
        $user->name = $findUserModel['name'];
        return $user;
    }
    public static function login(string $fd, string $uid, string $name): User {
        //先找fd
        $fdTable = TableUtil::fdTable();
        $findUid = $fdTable->get($fd, 'uid');
        if ($findUid === false) {
            //新用户
            $fdTable->set($fd, ['uid' => $uid]);
        }
        //找uid
        $userTable = TableUtil::userTable();
        $user = new User();
        $user->uid = $uid;
        $findUserModel = $userTable->get($uid);
        if ($findUserModel === false) {
            //新建用户
            $userTable->set($uid, [
                'uid' => $uid, 
                'name' => $name
            ]);
        } else {
            //重连到用户
            if ($findUserModel['name'] != $name) {
                //修改了名称，理论不可能，暂时先兼容
                $userTable->set($uid, [
                    'name' => $name
                ]);
            }
            $user->name = $name;
        }
        SocketUtil::contextSet(SocketUtil::CTX_UID, $uid);
        return $user;
    }
}