<?php

use App\Utils\SocketUtil;

require_once __DIR__. '/vendor/autoload.php';

$fdTable = new Swoole\Table(2048);
$fdTable->column('fd', Swoole\Table::TYPE_INT);
$fdTable->column('uid', Swoole\Table::TYPE_STRING, 8);
$fdTable->create();

$userTable = new Swoole\Table(1024);
$userTable->column('uid', Swoole\Table::TYPE_STRING, 8);
$userTable->column('name', Swoole\Table::TYPE_STRING, 32);
$userTable->column('roomId', Swoole\Table::TYPE_STRING, 8); //所在房间，断线不会改变，主动退出、换房、被踢出才会改变
$userTable->column('roomStatus', Swoole\Table::TYPE_INT); //当前状态 脚本会定时更新
$userTable->column('updatedAt', Swoole\Table::TYPE_INT); //活跃时间timestamp
$userTable->create();

$roomTable = new Swoole\Table(8);
$roomTable->column('id', Swoole\Table::TYPE_STRING, 8);
$roomTable->column('name', Swoole\Table::TYPE_STRING, 32);
$roomTable->column('ownerId', Swoole\Table::TYPE_STRING, 8); //无房主时，为空字符串
$roomTable->column('status', Swoole\Table::TYPE_INT); //当前状态
$roomTable->column('updatedAt', Swoole\Table::TYPE_INT); //活跃时间timestamp
$userTable->create();

$roomTable->set('test', [
    'id' => 'test',
    'name' => '测试房间',
    'status' => 1,
    'updatedAt' => time()
]);
$server = new \Swoole\WebSocket\Server("127.0.0.1", 9999);
$server->set([
    'worker_num' => 2,
    'heartbeat_check_interval' => 10,
    'heartbeat_idle_time' => 60,
]);
$server->fdTable = $fdTable;
$server->userTable = $userTable;
$server->roomTable = $roomTable;
echo "服务器启动\n";

$service = new \App\Service\SocketService();
$server->on('open', function (\Swoole\WebSocket\Server $server, $request) {
    echo "[{$request->fd}]握手成功\n";
});
$server->on('message', function (\Swoole\WebSocket\Server $server, $frame) use($service) {
    SocketUtil::contextSet(SocketUtil::CTX_SERVER, $server);
    SocketUtil::contextSet(SocketUtil::CTX_FD, $frame->fd);
    echo "[{$frame->fd}]原始消息:{$frame->data}\n";
    $service->onMessage($frame, $frame->fd);
});

$server->on('close', function ($server, $fd) use($service) {
    echo "[{$fd}]客户端关闭\n";
    $service->onFdClose($fd);
});


function checkAll($server) {
    SocketUtil::contextSet(SocketUtil::CTX_SERVER, $server);
    echo "状态检查\n";
    echo \App\Utils\TableUtil::genTableStats();
    echo "\n";
}
\Swoole\Timer::after(1000, function() use($server) {
    checkAll($server);
});
\Swoole\Timer::tick(30 * 1000, function() use($server) {
    checkAll($server);
});




$server->start();
