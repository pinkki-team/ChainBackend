<?php

namespace App\Service\Lexicon\Data;

use App\Service\Lexicon\LocalLexicon;
use App\Service\Lexicon\Word;
use Illuminate\Support\Arr;

class SimpleFile extends LocalLexicon {
    protected $libs = [];
    
    const FILE_EXTENSION = 'txt';
    const DIRECTORY_NAME = 'SimpleFile';

    public function getWords(int $count): array {
        return collect(Arr::random($this->libs, $count))
            ->map(function (string $word) {
                $w = new Word();
                $w->word = $word;
                $w->lexiconClass = get_class($this);
                return $w;
            })
            ->shuffle()
            ->all();
    }

    public function loadRaw(string $raw) {
        $rawWords = explode("\n", $raw);
        foreach ($rawWords as $rawWord) {
            $filtered = trim($rawWord);
            if (!empty($filtered)) {
                $this->libs []= $filtered;
            }
        }
    }
    public function getTotalCount(): int {
        return count($this->libs);
    }
}