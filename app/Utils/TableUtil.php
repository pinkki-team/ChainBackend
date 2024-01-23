<?php

namespace App\Utils;

use Swoole\Table;

class TableUtil {
    //todo 清理
    
    
    
    public static function fdTable(): Table {
        return SocketUtil::contextServer()->fdTable;
    }
    public static function userTable(): Table {
        return SocketUtil::contextServer()->userTable;
    }
    public static function roomTable(): Table {
        return SocketUtil::contextServer()->roomTable;
    }
    
    public static function dumpTable(Table $table): array {
        $res = [];
        foreach ($table as $row) {
            $res []= $row;
        }
        return $res;
    }
    
    
    public static function genTableStats(): string {
        $res = [];
        $fdTable = self::fdTable();
        $res []= "FDTable:数量:{$fdTable->count()},内存:{$fdTable->getMemorySize()}";
        foreach ($fdTable as $row) {
            $rr = [];
            foreach ($row as $k => $v) {
                $rr []= $v; 
            }
            $res []= implode(' ', $rr);
        }

        $userTable = self::userTable();
        $res []= "UserTable:数量:{$userTable->count()},内存:{$userTable->getMemorySize()}";
        foreach ($userTable as $row) {
            $rr = [];
            foreach ($row as $k => $v) {
                $rr []= $v;
            }
            $res []= implode(' ', $rr);
        }
        return implode("\n", $res);
    }
}