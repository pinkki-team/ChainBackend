<?php

namespace App\Utils;

use Swoole\Table;

class AdminUtil {
    //todo 清理
    public static function isUidAdmin(string $uid): bool {
        //TODO
        echo "uid:" . $uid ."\n";
        echo 'envuid:' . env('ADMIN_UID') . "\n";
        return $uid === env('ADMIN_UID');
    }
    
    public static function getAllTableData(): array {
        return [
            'fd' => TableUtil::dumpTable(TableUtil::fdTable()),
            'user' => TableUtil::dumpTable(TableUtil::userTable()),
            'room' => TableUtil::dumpTable(TableUtil::roomTable()),
        ];
    }
}