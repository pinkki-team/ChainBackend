<?php

use function Thermage\render;

if (!function_exists('vlog')) {
    function vlog(string $msg, ?string $color) {
        $arr = [$msg];
        foreach ($types as $type) {
            $arr []= "[$type]";
        }
        $fin = implode('', array_reverse($arr));
        echo "$fin\n";
        render(
            div('Stay RAD!')
                ->pl5()
                ->colorPink100()
                ->bgPink700()
                ->bold()
                ->italic()
        );
    }
}