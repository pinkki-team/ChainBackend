<?php

namespace App\Service\Lexicon;

use App\Service\BaseService;
use App\Service\Lexicon\Data\SimpleFile;

class LexiconService extends BaseService {
    /** @var class-string<Lexicon>[]|Lexicon[]  */
    const LEXICONS = [
        SimpleFile::class,
    ];
    
    public function check() {
        foreach (self::LEXICONS as $lexiconClass) {
            $lexiconClass::load();
        }
    }
    
    /** @return Lexicon[] */
    public function getAll(): array {
        $res = [];
        foreach (self::LEXICONS as $lexiconClass) {
            $res = array_merge($res, $lexiconClass::load());
        }
        return $res;
    }
}