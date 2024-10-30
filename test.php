<?php

require_once __DIR__. '/vendor/autoload.php';
require_once __DIR__. '/app/helpers.php';

$lexiconService = new \App\Service\Lexicon\LexiconService();
$lexicons = $lexiconService->getAll();
$lex1 = array_pop($lexicons);
$words = array_map(function (\App\Service\Lexicon\Word $word) { return $word->word; }, $lex1->getWords(4));
vlog("获取4个词:" . implode(',', $words));