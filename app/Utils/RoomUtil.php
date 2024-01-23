<?php

namespace App\Utils;

use App\Entity\Room;
use App\Entity\RoomMessage;
use App\Entity\User;

class RoomUtil {
    public static function getRoom(string $roomId, bool $withMembers = false): ?Room {
        $table = TableUtil::roomTable();
        $roomModel = $table->get($roomId);
        if ($roomModel === false) return null;
        $room = Room::fromModel($roomModel);
        
        if ($withMembers) {
            $room->members = self::getMembers($roomId);
        }
        return $room;
    }
    public static function getMaxMemberCount(string $roomId): int {
        return 16; //暂时写死
    }
    public static function getMemberCount(string $roomId): int {
        return count(self::getMembers($roomId));
    }
    /** @return User[] */
    public static function getMembers(string $roomId): array {
        $userTable = TableUtil::userTable();
        $res = [];
        foreach ($userTable as $userModel) {
            $user = User::fromModel($userModel);
            if (
                $user->roomId === $roomId
                && in_array($user->roomStatus, [User::ROOM_STATUS_NORMAL, User::ROOM_STATUS_DISCONNECTED])
            ) {
                $res []= $user;
            }
        }
        return $res;
    }
    public static function getMemberUids(string $roomId): array {
        $members = self::getMembers($roomId);
        return array_map(function(User $member) {
            return $member->uid;
        }, $members);
    }
    
    public static function getFds(string $roomId): array {
        $members = self::getMembers($roomId);
        $res = [];
        foreach ($members as $member) {
            if (!is_null($member->activeFd)) $res []= $member->activeFd;
        }
        return $res;
    }
    public static function pushRoomInfoResponse(Room $room) {
        SocketUtil::pushSuccessWithData((array)$room);
    }
    //用户彻底离开房间，需要发推送
    public static function userLeftRoomEvent(string $uid, string $roomId) {
        //TODO
    }
    
    
    
    
    
    public static function pushRoomMessage(Room $room, RoomMessage $message) {
        
    }
}