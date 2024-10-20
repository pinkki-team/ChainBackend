<?php

namespace App\Utils;

use App\Entity\User;
use App\Exception\BadFDException;
use App\Exception\LoginException;
use Swoole\Table;

class FdControl {
    public static function fd2Uid(int $fd): ?string {
        $fd = strval($fd);
        //先找fd
        $fdTable = TableUtil::fdTable();
        $uid = $fdTable->get($fd, 'uid');
        if ($uid === false) {
            return null;
        }
        return $uid;
    }
    
    public static function getFdsByUids(array $uids): array {
        $fdTable = TableUtil::fdTable();
        $res = [];
        foreach ($fdTable as $row) {
            if (in_array($row['uid'], $uids)) {
                $res []= $row['fd'];
            }
        }
        return $res;
    }
    public static function uid2User(string $uid): ?User {
        //找uid
        $userTable = TableUtil::userTable();
        $findUserModel = $userTable->get($uid);
        if ($findUserModel === false) {
            return null;
        }
        return User::fromModel($findUserModel);
    }
    /**  @throws BadFDException */
    public static function fd2User(string $fd): User {
        //先找fd
        $uid = self::fd2Uid($fd);
        if (is_null($uid)) {
            throw new BadFDException();
        }
        //找uid
        $user = self::uid2User($uid);
        if (is_null($user)) throw new BadFDException();
        return $user;
    }
    public static function login(string $fd, string $uid, string $name, array $extra = []) {
        //先找fd
        $fdTable = TableUtil::fdTable();
        $findUid = $fdTable->get($fd, 'uid');
        if ($findUid === false) {
            //新用户
            $fdTable->set($fd, ['fd' => intval($fd), 'uid' => $uid]);
        } else {
            if ($findUid !== $uid) {
                //TODO fd不变而uid变化
            }
        }
        //找uid
        $userTable = TableUtil::userTable();
        $findUserModel = $userTable->get($uid);
        if ($findUserModel === false) {
            //新建用户
            $userTable->set($uid, [
                'uid' => $uid, 
                'name' => $name,
                'activeFd' => intval($fd),
                'roomStatus' => User::ROOM_STATUS_NONE,
                'ping' => -1,
                'extra' => json_encode($extra),
            ]);
        } else {
            //重连到用户
            if ($findUserModel['name'] != $name) {
                //修改了名称，理论不可能，暂时先兼容
                $userTable->set($uid, [
                    'name' => $name
                ]);
            }
            //如果新fd和uid的activeFd不一致，将其下线
            $activeFd = $findUserModel['activeFd'];
            if (!is_null($activeFd) && $fd !== $findUserModel['activeFd']) {
                $fdTable->delete(strval($activeFd));
                $userTable->set($uid, [
                    'activeFd' => intval($fd),
                ]);
            }
        }
        SocketUtil::contextSet(SocketUtil::CTX_UID, $uid);
    }
}