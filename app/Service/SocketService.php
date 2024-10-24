<?php

namespace App\Service;

use App\Entity\Room;
use App\Entity\User;
use App\Exception\LoginException;
use App\Service\Actions\AdminActions;
use App\Utils\AdminUtil;
use App\Utils\ChatUtil;
use App\Utils\FdControl;
use App\Utils\ReconnectUtil;
use App\Utils\RoomUtil;
use App\Utils\SocketUtil;
use App\Utils\TableUtil;
use Swoole\WebSocket\Frame;

class SocketService extends BaseService {
    use AdminActions;
    static $nameDictTest = [];
    
    const NO_AUTH_LIST = [
        'login',
        'ping'
    ];


    public function onConnect(
        int $fd,
        array $get
    ) {
        $name = $get['name'] ?? null;
        $roomId = $get['rid'] ?? null;
        $uid = $get['uid'] ?? null;


        //extra字段
        $extra = [];
        if (!is_null($source = $get['source'] ?? null)) {
            $extra[User::EKEY_SOURCE] = $source;
        }

//        var_dump("fd: $fd, name: $name, roomId: $roomId, uid: $uid");
        if ($name && $uid && $fd) {
            FdControl::login($fd, $uid, $name, $extra);
        }
        if ($roomId) {
            $room = RoomUtil::getRoom($roomId, true);
            if (is_null($room)) {
                SocketUtil::push($fd, 'initRoomNotExist');
                return;
            }
            SocketUtil::push($fd, 'init', $room->infoArray());
        }

    }
    public function onMessage(Frame $frame, int $fd) {
        $raw = json_decode($frame->data, true);
        if (empty($raw)) return;
        SocketUtil::contextSet(SocketUtil::CTX_REQUEST, $raw);
        $action = $raw['action'] ?? null;
        if (empty($action)) return;
        $data = $raw['data'] ?? [];
        
        if (!in_array($action, self::NO_AUTH_LIST)) {
            //需校验的接口
            $uid = FdControl::fd2Uid($fd);
            if (is_null($uid)) {
                //找不到FD
                SocketUtil::push403();
                return;
            }
            SocketUtil::contextSet(SocketUtil::CTX_UID, $uid);
        }
        
        $actionMethod = 'action' . ucfirst($action);
        if (method_exists($this, $actionMethod)) {
            $this->$actionMethod($data);
        }
        
        //更新ping
    }
    public function actionPing(array $data) {
        SocketUtil::pushSuccess();
    }
    public function actionLogin(array $data) {
        $uid = $data['uid'];
        $name = $data['name'];
        if (strlen($uid) !== 8) {
            SocketUtil::pushError('uid错误');
            return;
        }
        if (strlen($name) > 32) {
            SocketUtil::pushError('姓名过长');
            return;
        }
        FdControl::login(strval(SocketUtil::contextFd()), $uid, $name);
        SocketUtil::pushSuccess();
    }
    const ERS_STATUS_NORMAL = 1; //基础
    const ERS_STATUS_RECONNECT = 2; //重连
    public function actionJoinRoom(array $data) {
        $roomId = $data['roomId'];
        //首先检查房间是否存在
        $room = RoomUtil::getRoom($roomId, true);
        if (is_null($room)) {
            SocketUtil::pushError('房间不存在');
            return;
        }

        $isReconnect = false;
        $reconnectType = 1;
        $res = [
            'status' => self::ERS_STATUS_NORMAL,
        ];
        //首先检查roomstatus
        $user = User::current();
        switch ($user->roomStatus) {
            case User::ROOM_STATUS_NORMAL:
                if ($user->roomId !== $roomId) {
                    //意外情况，暂时只返回房间信息
                    $isReconnect = true;
                } else {
                    //更换房间
                    RoomUtil::userLeftRoomEvent($user, $roomId, true);
                }
                break;
            case User::ROOM_STATUS_DISCONNECTED_1:
            case User::ROOM_STATUS_DISCONNECTED_2:
                if ($user->roomId === $roomId) {
                    //正常重连
                    $isReconnect = true;
                    //深断线的用户，回到房间需要推送重连通知
                    if ($user->roomStatus === User::ROOM_STATUS_DISCONNECTED_2) {
                        $reconnectType = 2;
                    }
                } else {
                    //更换房间
                    RoomUtil::userLeftRoomEvent($user, $roomId, true);
                }
                break;
            case User::ROOM_STATUS_NONE:
                //这两种情况，用户一定没有roomId，就正常加入
                break;
        }
        
        if ($isReconnect) {
            $res['room'] = $room->infoArray();
            $res['status'] = self::ERS_STATUS_RECONNECT;
            $user->updateValues([
                'updatedAt' => time(),
                'roomStatus' => User::ROOM_STATUS_NORMAL,
            ]);
            ReconnectUtil::reconnectClearTimer($user, $roomId);
            RoomUtil::userReconnectEvent($user, $roomId, $reconnectType);
            SocketUtil::pushSuccessWithData($res);
            return;
        }
        
        //新加入逻辑
        //判断房间是否可加入(纯房间角度:满人,状态)
        $canJoinRes = $room->canJoinRes();
        if (is_string($canJoinRes)) {
            SocketUtil::pushError($canJoinRes);
            return;
        }
        
        //处理加入
        $user->updateValues([
            'roomId' => $room->id,
            'updatedAt' => time(),
            'roomStatus' => User::ROOM_STATUS_NORMAL,
        ]);
        RoomUtil::userEnterRoomEvent($user, $room->id);
        $setOwner = empty($room->members);
        if ($setOwner) {
            $room->updateValue('ownerId', $user->uid);
        }
        $room = RoomUtil::getRoom($roomId, true); //刷新状态
        $res['room'] = $room->infoArray();
        SocketUtil::pushSuccessWithData($res);

        //如果为空 设置房主
        if ($setOwner) {
            RoomUtil::pushSetAdmin($room->id, $user);
        }
    }
    
    public function actionChat(array $data) {
        $user = User::current();
        $roomId = $data['roomId'] ?? null;
        if ($user->roomId !== $roomId) {
            SocketUtil::pushError("您已不在房间内!");
            return;
        }
        ReconnectUtil::check($user, $roomId);
        
        $content = $data['content'];
        $chatReviewRes = ChatUtil::reviewChat($content);
        if (is_string($chatReviewRes)) {
            SocketUtil::pushError($chatReviewRes);
            return;
        }
        
        $room = RoomUtil::getRoom($roomId);
        if (is_null($room)) {
            SocketUtil::pushError("房间已不存在!");
            return;
        }
        RoomUtil::pushChat($room->id, $user, $content);
        SocketUtil::pushSuccess();
    }
    
    public function actionLeaveRoom(array $data) {
        $user = User::current();
        $roomId = $data['roomId'] ?? null;
        if ($user->roomId !== $roomId) {
            SocketUtil::pushError("您已不在房间内!");
            return;
        }
        if (!in_array($user->roomStatus, [
            User::ROOM_STATUS_NORMAL,
            User::ROOM_STATUS_DISCONNECTED_1,
            User::ROOM_STATUS_DISCONNECTED_2,
        ])) {
            SocketUtil::pushError("您已不在房间内!");
            return;
        }
        RoomUtil::userLeftRoomEvent($user, $roomId, true);
        $user->updateValues([
            'roomId' => null,
            'roomStatus' => User::ROOM_STATUS_NONE,
        ]);
        SocketUtil::pushSuccess();
    }
    
    
    
    
    
    
    
    
    public function onFdClose(int $fd) {
        $uid = FdControl::fd2Uid($fd);
        if (!is_null($uid)) {
            $user = FdControl::uid2User($uid);
            if (!is_null($user)) {
                if ($user->roomStatus === User::ROOM_STATUS_NORMAL && !empty($user->roomId)) {
                    //浅断线,更新状态为浅断线，浅断线持续一定事件后，处理为深断线并且做后续操作
                    ReconnectUtil::disconnect1($user, $user->roomId);
                }
                if ($user->activeFd === $fd) {
                    $user->updateValue('activeFd', -1);
                }
            }
            $fdTable = TableUtil::fdTable();
            $fdTable->delete(strval($fd));
        }
    }
}