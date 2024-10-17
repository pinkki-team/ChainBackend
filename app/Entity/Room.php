<?php

namespace App\Entity;

use App\Entity\Word\DefaultWordProvider;
use App\Utils\RoomUtil;
use App\Utils\SocketUtil;

class Room extends AbstractEntity {
    const TABLE = 'roomTable';
    
    
    const STATUS_WAITING = 0; //初始状态，正在等待
    
    
    
    
    public $id;
    public $name;
    public $status;
    public $updatedAt;
    public $ownerId;

    const WORD_GROUP_DEFAULT = 'default';
    public $wordGroupType;
    public $wordGroupId;
    
    public $members = [];
    
    
    //若返回null说明可加入
    public function canJoinRes(): ?string {
        if ($this->status !== Room::STATUS_WAITING) {
            return '游戏已经开始，无法中途加入';
        }
        if (RoomUtil::getMemberCount($this->id) >= RoomUtil::getMaxMemberCount($this->id)) {
            return '房间人数已满';
        }
        return null;
    }
    
    
    public function generateWords(int $count): array {
        switch ($this->wordGroupType) {
            case Room::WORD_GROUP_DEFAULT:
                return DefaultWordProvider::generate($count);
            default:
                return [];
        }
    }
    
    public function infoArray(): array {
        $raw = (array)$this;
        $raw['max'] = 16;
        $raw['canJoin'] = $this->canJoinRes() === null;
        //词库关联
        return $raw;
    }
    public static function mainKey(): string {
        return 'id';
    }
}