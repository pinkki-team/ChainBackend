<?php

namespace App\Entity;

class Room extends AbstractEntity {
    const STATUS_WAITING = 0; //初始状态，正在等待
    public $id;
    public $name;
    public $status;
    public $updatedAt;
    public $ownerId;
    
    public $members = [];
    
    public function getWordGroupName(): string {
        return '待定';
    }
    
    public function infoArray(): array {
        $raw = (array)$this;
        return $raw;
    }
}