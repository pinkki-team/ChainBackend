<?php

namespace App\Service;

use App\Entity\Room;
use App\Entity\User;
use App\Exception\LoginException;
use App\Service\Actions\AdminActions;
use App\Utils\AdminUtil;
use App\Utils\FdControl;
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
            case User::ROOM_STATUS_DISCONNECTED:
                if ($user->roomId === $roomId) {
                    //正常重连
                    $isReconnect = true;
                    return;
                } else {
                    //更换房间
                    RoomUtil::userLeftRoomEvent($user, $roomId, true);
                }
                break;
            case User::ROOM_STATUS_ALREADY_DISCONNECTED:
            case User::ROOM_STATUS_NONE:
                //这两种情况，用户一定没有roomId，就正常加入
                break;
        }
        
        if ($isReconnect) {
            $res['status'] = self::ERS_STATUS_RECONNECT;
            $user->updateValues([
                'updatedAt' => time(),
                'roomStatus' => User::ROOM_STATUS_NORMAL,
            ]);
            RoomUtil::userReconnectEvent($user, $roomId);
            SocketUtil::pushSuccessWithData($res);
            return;
        }
        
        //新加入逻辑
        //判断房间是否可加入
        if ($room->status !== Room::STATUS_WAITING) {
            SocketUtil::pushError('游戏已经开始，无法中途加入');
            return;
        }
        if (RoomUtil::getMemberCount($roomId) >= RoomUtil::getMaxMemberCount($roomId)) {
            SocketUtil::pushError('房间人数已满');
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
        if ($user->roomStatus === User::ROOM_STATUS_DISCONNECTED) {
            $user->updateValues([
                'updatedAt' => time(),
                'roomStatus' => User::ROOM_STATUS_NORMAL,
            ]);
            RoomUtil::userReconnectEvent($user, $roomId);
        }
        
        $content = $data['content'];
        if (mb_strlen($content) > 50) {
            SocketUtil::pushError("文字过长!");
            return;
        }
        
        $room = RoomUtil::getRoom($roomId);
        if (is_null($room)) {
            SocketUtil::pushError("房间已不存在!");
            return;
        }
        RoomUtil::pushChat($room, $user, $content);
        SocketUtil::pushSuccess();
    }
    
    public function actionLeaveRoom(array $data) {
        $user = User::current();
        $roomId = $data['roomId'] ?? null;
        if ($user->roomId !== $roomId) {
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
                
                if (!empty($user->roomId)) {
                    //处理用户断线
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