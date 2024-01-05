<?php

namespace App\Entity;

class User {
    
    const ROOM_STATUS_NONE = 0; //正常不在房间里
    const ROOM_STATUS_NORMAL = 1; //正常在房间里
    const ROOM_STATUS_DISCONNECTED = 2; //断线
    const ROOM_STATUS_ALREADY_DISCONNECTED = 3; //被自动脚本判断为断线。客户端拿到这个状态后，会提醒用户已断线更新为0

    public $uid;
    public $name;
    /** @var string */
    public $roomId;
    /** @var int */
    public $roomStatus;
}