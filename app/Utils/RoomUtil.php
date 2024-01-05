<?php

namespace App\Utils;

use App\Entity\Room;

class RoomUtil {
    public static function getRoom(string $roomId): ?Room {
        $table = TableUtil::roomTable();
        $roomModel = $table->get($roomId);
        if ($roomModel === false) return null;
        $room = new Room();
        $room->id = $roomId;
        $room->name = $room['name'];
        $room->updatedAt = $room['updatedAt'];
        $room->status = $room['status'];
        $room->ownerId = $room['ownerId'];
        return $room;
    }
}