<?php

namespace App\Entity;

class RoomMessage {
    const TYPE_CHAT = 1;
    const TYPE_SET_ADMIN = 2; //用户成为房主
    const TYPE_USER_JOIN = 3; //用户进入房间
    const TYPE_USER_LEFT_INTENT = 4; //用户主动离开房间
    const TYPE_USER_LEFT_DISCONNECTED = 5; //用户离开房间(断线时间过长)
    
    public $type;
    public $fromUserId;
    public $fromUserName;
    public $content;
    
    public function toArray(): array {
        switch ($this->type) {
            case self::TYPE_CHAT:
                return [$this->type, $this->fromUserId, $this->fromUserName, $this->content];
            case self::TYPE_SET_ADMIN:
            case self::TYPE_USER_JOIN:
            case self::TYPE_USER_LEFT_DISCONNECTED:
            case self::TYPE_USER_LEFT_INTENT:
                return [$this->type, $this->fromUserId, $this->fromUserName];
        }
    }
}