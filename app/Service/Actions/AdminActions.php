<?php

namespace App\Service\Actions;

use App\Entity\User;
use App\Utils\AdminUtil;
use App\Utils\RoomUtil;
use App\Utils\SocketUtil;

trait AdminActions {

    public function actionAdminTest(array $data) {
        $user = User::current();
        if (!AdminUtil::isUidAdmin($user->uid)) {
            SocketUtil::pushError('403');
            return;
        }
        $room = RoomUtil::getRoom('test');
        SocketUtil::pushSuccessWithData([
            'type' => gettype($room->updatedAt),
            'value' => $room->updatedAt,
        ]);
    }
}