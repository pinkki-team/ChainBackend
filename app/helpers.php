<?php

use function Thermage\render;
use function Thermage\div;
use function Thermage\breakline;

if (!function_exists('vlog')) {
    function vlog(string $msg, ?string $color = 'blue') {
        $time = date('H:i:s');
        render(div("[color=green][{$time}][/color][color={$color}]{$msg}[/color]")->clearfix());
        render(breakline());
    }
    function vlogError(string $msg) {
        vlog($msg, 'red');
    }
    function vlogDebug(string $msg) {
        vlog($msg, 'gray');
    }
}