<?php

namespace App\Service;

abstract class BaseService {
    public function log(string $content) {
        vlog($content);
    }
}