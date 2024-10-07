<?php

namespace App\Entity;

use App\Entity\Word\DefaultWordProvider;

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
        //词库关联
        return $raw;
    }
    public static function mainKey(): string {
        return 'id';
    }
}