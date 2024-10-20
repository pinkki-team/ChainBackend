<?php

namespace App\Utils;

use App\Entity\Room;
use App\Entity\User;

class ReconnectUtil {

    const DISCONNECT_REAL_TIME = 30; //超过30秒浅断线就会深断线
    public static function check(User $user, string $roomId) {
        if ($user->roomId !== $roomId) return;
        if (in_array($user->roomStatus, [User::ROOM_STATUS_DISCONNECTED_1, User::ROOM_STATUS_DISCONNECTED_2])) {
            $user->updateValues([
                'roomStatus' => User::ROOM_STATUS_NORMAL,
                'updatedAt' => time()
            ]);
            if ($user->roomStatus === User::ROOM_STATUS_DISCONNECTED_1) RoomUtil::userReconnectEvent($user, $roomId, 1);
            else RoomUtil::userReconnectEvent($user, $roomId, 2);
        }
    }
}