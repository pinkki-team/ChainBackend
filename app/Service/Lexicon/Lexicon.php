<?php

namespace App\Service\Lexicon;

abstract class Lexicon {
    /** @return static[] */
    abstract public static function load(): array;
    /** @return Word[] */
    abstract public function getWords(int $count): array;
    abstract public function getTotalCount(): int;
    
    /** 词库名 */
    public $name;
    /** 作者 */
    public $author;
    /** 描述 */
    public $description;
}