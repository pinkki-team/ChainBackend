<?php

namespace App\Utils;

class ChatUtil {
    //todo
    public static function reviewChat(string $content): ?string {
        if (mb_strlen($content) > 50) {
            return "文字过长!";
        }
        return null;
    }
}