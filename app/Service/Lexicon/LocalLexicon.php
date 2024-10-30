<?php

namespace App\Service\Lexicon;

use Symfony\Component\Finder\Finder;

abstract class LocalLexicon extends Lexicon {
    const DIRECTORY_NAME = null;
    public static function getDirectoryName(): string {
        if (!is_null(static::DIRECTORY_NAME)) return static::DIRECTORY_NAME;
        //获取当前类名
        $className = get_called_class();
        $className = explode('\\', $className);
        return end($className);
    }
    const FILE_EXTENSION = null;
    public static function getFileExtension(): ?string {
        return static::FILE_EXTENSION;
    }
    /** @return static[] */
    public static function load(): array {
        $finder = new Finder();
        $finder->files()->in(lexicon_path(static::getDirectoryName()));
        if (!is_null($extension = static::getFileExtension())) {
            $finder->name("*.{$extension}");
        }
        if (!$finder->hasResults()) {
            return [];
        }
        $res = [];
        foreach ($finder as $file) {
            $lexicon = new static();
            $lexicon->name = $file->getFilenameWithoutExtension();
            $lexicon->loadRaw($file->getContents());
            $res []= $lexicon;
        }
        return $res;
    }
    
    
    
    abstract public function loadRaw(string $raw);
}