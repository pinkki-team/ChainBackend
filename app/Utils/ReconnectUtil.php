<?php

namespace App\Utils;

use App\Entity\User;
use Illuminate\Support\Str;
use Swoole\Timer;

class ReconnectUtil {

    const DISCONNECT_REAL_TIME = 10; //超过30秒浅断线就会深断线
    const DISCONNECT_LEFT_TIME = 30; //深断线30秒就会退出房间
    
    public static function reconnectClearTimer(User $user, string $roomId) {
        $timerId = $user->extraGet(User::EKEY_DISCONNECT_TIMER); 
        if ($timerId) {
            $user->extraDel(User::EKEY_DISCONNECT_TIMER);
        }
    }
    
    
    //浅断线触发计时
    public static function disconnect1(User $user, string $roomId) {
        //获取用户上一次的断线timer
        $uid = $user->uid;
        $randId = Str::random(6);
        Timer::after(self::DISCONNECT_REAL_TIME * 1000, function ($randId, $uid, $roomId) {
            ReconnectUtil::disconnectTimed($randId, $uid, $roomId);
        }, $randId, $uid, $roomId);
        $user->extraSet(User::EKEY_DISCONNECT_TIMER, $randId);
        $user->updateValues([
            'roomStatus' => User::ROOM_STATUS_DISCONNECTED_1,
        ]);
        vlog("[用户浅断线]{$user->name}");
    }
    
    public static function disconnectTimed(string $timerId, string $uid, string $roomId) {
        $user = FdControl::uid2User($uid);
        if (!$user) return;
        if ($user->extraGet(User::EKEY_DISCONNECT_TIMER) !== $timerId) return;
        if ($user->roomId !== $roomId) return;
        if ($user->roomStatus !== User::ROOM_STATUS_DISCONNECTED_1) return;
        RoomUtil::userDisconnectEvent($user, $roomId);
        $randId = Str::random(6);
        $user->updateValues([
            'roomStatus' => User::ROOM_STATUS_DISCONNECTED_2,
        ]);
        $user->extraSet(User::EKEY_DISCONNECT_TIMER, $randId);
        Timer::after(self::DISCONNECT_LEFT_TIME * 1000, function ($randId, $uid, $roomId) {
            ReconnectUtil::disconnectLeftTimed($randId, $uid, $roomId);
        }, $randId, $uid, $roomId);
        vlog("[用户深断线]{$user->name}");
    }

    public static function disconnectLeftTimed(string $timerId, string $uid, string $roomId) {
        $user = FdControl::uid2User($uid);
        if (!$user) return;
        if ($user->extraGet(User::EKEY_DISCONNECT_TIMER) !== $timerId) return;
        if ($user->roomId !== $roomId) return;
        if ($user->roomStatus !== User::ROOM_STATUS_DISCONNECTED_2) return;
        $user->extraDel(User::EKEY_DISCONNECT_TIMER);
        $user->updateValues([
            'roomStatus' => User::ROOM_STATUS_NONE,
            'roomId' => null
        ]);
        RoomUtil::userDisconnectLeftEvent($user, $roomId);
        vlog("[用户深断线退出房间]{$user->name}");
    }
    
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