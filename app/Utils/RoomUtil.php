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
                && in_array($user->roomStatus, [User::ROOM_STATUS_NORMAL, User::ROOM_STATUS_DISCONNECTED_1, User::ROOM_STATUS_DISCONNECTED_2])
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
    //用户加入房间事件
    public static function userEnterRoomEvent(User $user, string $roomId) {
        self::pushUserJoin($roomId, $user);
    }
    //用户深断线事件
    public static function userDisconnectEvent(User $user, string $roomId) {
        self::pushUserDisconnect($roomId, $user);
    }
    //用户重连房间事件
    public static function userReconnectEvent(User $user, string $roomId, int $reconnectType) {
        if ($reconnectType === 1) {
            self::pushUserReconnect1($roomId, $user);
        } else {
            self::pushUserReconnect2($roomId, $user);
        }
    }
    //用户主动离开房间
    public static function userLeftRoomEvent(User $user, string $roomId, bool $isIntent) {
        if ($isIntent) self::pushUserLeftIntent($roomId, $user);
        else self::pushUserLeftIntent($roomId, $user);
    }
    
    //推送
    public static function pushSetAdmin(string $roomId, User $user) {
        $msg = new RoomMessage();
        $msg->type = RoomMessage::TYPE_SET_ADMIN;
        $msg->fromUserId = $user->uid;
        $msg->fromUserName = $user->name;
        self::pushRoomMessage($roomId, $msg);
    }
    //用户加入
    public static function pushUserJoin(string $roomId, User $user) {
        $msg = new RoomMessage();
        $msg->type = RoomMessage::TYPE_USER_JOIN;
        $msg->fromUserId = $user->uid;
        $msg->fromUserName = $user->name;
        self::pushRoomMessage($roomId, $msg);
    }
    //用户深断线
    public static function pushUserDisconnect(string $roomId, User $user) {
        $msg = new RoomMessage();
        $msg->type = RoomMessage::TYPE_USER_DISCONNECT;
        $msg->fromUserId = $user->uid;
        $msg->fromUserName = $user->name;
        self::pushRoomMessage($roomId, $msg);
    }
    //用户回归
    public static function pushUserReconnect1(string $roomId, User $user) {
        $msg = new RoomMessage();
        $msg->type = RoomMessage::TYPE_USER_RECONNECT_1;
        $msg->fromUserId = $user->uid;
        $msg->fromUserName = $user->name;
        self::pushRoomMessage($roomId, $msg);
    }
    //用户回归
    public static function pushUserReconnect2(string $roomId, User $user) {
        $msg = new RoomMessage();
        $msg->type = RoomMessage::TYPE_USER_RECONNECT_2;
        $msg->fromUserId = $user->uid;
        $msg->fromUserName = $user->name;
        self::pushRoomMessage($roomId, $msg);
    }
    public static function pushUserLeftIntent(string $roomId, User $user) {
        $msg = new RoomMessage();
        $msg->type = RoomMessage::TYPE_USER_LEFT_INTENT;
        $msg->fromUserId = $user->uid;
        $msg->fromUserName = $user->name;
        self::pushRoomMessage($roomId, $msg);
    }
    public static function pushChat(string $roomId, User $user, string $content) {
        $msg = new RoomMessage();
        $msg->type = RoomMessage::TYPE_CHAT;
        $msg->fromUserId = $user->uid;
        $msg->fromUserName = $user->name;
        $msg->content = $content;
        self::pushRoomMessage($roomId, $msg);
    }
    public static function pushRoomMessage(string $roomId, RoomMessage $message) {
        $memberFds = self::getFds($roomId);
        SocketUtil::pushGroup($memberFds, 'roomMessage', $message->toArray());
    }
}