<?php

namespace App\Utils;

class AdminUtil {
    public static function isUidAdmin(string $uid): bool {
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