<?php

namespace App\Entity;

use App\Utils\SocketUtil;
use App\Utils\TableUtil;
use Swoole\Table;

/**
 * @mixin HasExtra
 */
trait HasExtraTrait {
    public function extraSet(string $key, $value) {
        $extra = $this->getExtra();
        $extra[$key] = $value;
        $this->setExtra($extra);
    }
    public function extraDel(string $key) {
        $extra = $this->getExtra();
        unset($extra[$key]);
        $this->setExtra($extra);
    }
    public function extraGet(string $key, $default = null) {
        return $this->getExtra()[$key] ?? $default;
    }
    public function extraIncr(string $key, int $value) {
        $def = $this->extraGet($key, 0);
        $this->extraSet($key, $def + $value);
    }
    
}