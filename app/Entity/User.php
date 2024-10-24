<?php

namespace App\Entity;

use App\Utils\FdControl;
use App\Utils\SocketUtil;

class User extends AbstractEntity implements HasExtra {
    use HasExtraTrait;
    const TABLE = 'userTable';
    const JSON_KEYS = ['extra'];

    //使用这个方法，默认一定有user
    public static function current(): User {
        $contextUser = SocketUtil::contextUser();
        if (!is_null($contextUser)) return $contextUser;
        $contextUid = SocketUtil::contextUid();
        $contextUser = FdControl::uid2User($contextUid);
        SocketUtil::contextSet(SocketUtil::CTX_USER, $contextUser);
        return $contextUser;
    }
    const ROOM_STATUS_NONE = 0; //正常不在房间里
    const ROOM_STATUS_NORMAL = 1; //正常在房间里
    const ROOM_STATUS_DISCONNECTED_1 = 2; //浅断线(socket中断)
    const ROOM_STATUS_DISCONNECTED_2 = 3; //深断线(浅断线过久)

    const EKEY_SOURCE = 'source';
    const EKEY_DISCONNECT_TIMER = 'disconnectTimer';

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
    public $ping; //-1为初始化状态
    /** @var array */
    public $extra;

    public static function mainKey(): string {
        return 'uid';
    }

    public function getExtra(): array {
        return $this->extra;
    }
    public function setExtra(array $extra) {
        $this->updateValue('extra', $extra);
    }
}