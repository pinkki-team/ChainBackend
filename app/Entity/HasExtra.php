<?php

namespace App\Entity;

use App\Utils\SocketUtil;
use App\Utils\TableUtil;
use Swoole\Table;

/**
 * @property array $extra
 */
interface HasExtra {
    public function getExtra(): array;
    public function extraSet(string $key, $value);
    public function extraDel(string $key);
    public function extraGet(string $key, $default = null);
    public function extraIncr(string $key, int $value);
    public function setExtra(array $extra);
}