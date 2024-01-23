<?php

namespace App\Entity;

use App\Utils\FdControl;
use App\Utils\SocketUtil;

class User extends AbstractEntity {
    const TABLE = 'userTable';
    
    //使用这个方法，默认一定有user
    public static function current(): User {
        $contextUser = SocketUtil::contextUser();
        if (!is_null($contextUser)) return $contextUser;
        $contextUid = SocketUtil::contextUid();
        return FdControl::uid2User($contextUid);
    }
    const ROOM_STATUS_NONE = 0; //正常不在房间里
    const ROOM_STATUS_NORMAL = 1; //正常在房间里
    const ROOM_STATUS_DISCONNECTED = 2; //断线
    const ROOM_STATUS_ALREADY_DISCONNECTED = 3; //被自动脚本判断为断线。客户端拿到这个状态后，会提醒用户已断线更新为0

    /** @var string */
    public $uid;
    /** @var int */
    public $activeFd;
    public $name;
    /** @var string */
    public $roomId;
    /** @var int */
    public $roomStatus;
    /** @var int */
    public $updatedAt;
    /** @var int */
    public $ping;

    public static function mainKey(): string {
        return 'uid';
    }
}