<?php

namespace App\Entity\Word;

use Illuminate\Support\Arr;

class DefaultWordProvider extends BaseWordProvider {
    const WORDS = [];
    public static function generate(int $count): array {
        //默认count不会大于48, 同时word数量不会小于48
        return Arr::random(static::WORDS, $count);
    }
}