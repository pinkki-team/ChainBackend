<?php

use Swoole\Coroutine;

require_once __DIR__. '/vendor/autoload.php';

//获取命令行输入
$uid = $argv[1] ?? null;
$name = $argv[2] ?? null;

function push(Coroutine\Http\Client $client, string $type, array $data = []) {
    $data['type'] = $type;
    $data['requestId'] = \Illuminate\Support\Str::random(8);
    $client->push(json_encode($data));
}
\Swoole\Coroutine\run(function () use($uid, $name) {
    $cli = new Swoole\Coroutine\Http\Client('127.0.0.1', 9999);
    $ret = $cli->upgrade('/');
    if ($ret) {
        push($cli, 'login', ['uid' => $uid, 'name' => $name]);
    }
    $res = $cli->recv();
});