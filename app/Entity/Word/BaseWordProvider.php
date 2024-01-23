<?php

namespace App\Entity\Word;

abstract class BaseWordProvider {
    abstract public static function generate(int $count): array;
}