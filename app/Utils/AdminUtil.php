<?php

namespace App\Utils;

use Swoole\Table;

class AdminUtil {
    //todo 清理
    public static function isUidAdmin(string $uid): bool {
        //TODO
        return $uid === getenv('ADMIN_UID');
    }
    
}