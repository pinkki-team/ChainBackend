<?php

use App\Utils\SocketUtil;

require_once __DIR__. '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

/********************************** 建表 **********************************/

$fdTable = new Swoole\Table(2048);
$fdTable->column('fd', Swoole\Table::TYPE_INT);
$fdTable->column('uid', Swoole\Table::TYPE_STRING, 8);
$fdTable->create();

$userTable = new Swoole\Table(1024);
$userTable->column('uid', Swoole\Table::TYPE_STRING, 8);
$userTable->column('activeFd', Swoole\Table::TYPE_INT);
$userTable->column('name', Swoole\Table::TYPE_STRING, 32);
$userTable->column('roomId', Swoole\Table::TYPE_STRING, 8); //所在房间，断线不会改变，主动退出、换房、被踢出才会改变
$userTable->column('roomStatus', Swoole\Table::TYPE_INT); //当前状态 脚本会定时更新
$userTable->column('updatedAt', Swoole\Table::TYPE_INT); //活跃时间timestamp
$userTable->column('ping', Swoole\Table::TYPE_INT);
$userTable->create();

$roomTable = new Swoole\Table(8);
$roomTable->column('id', Swoole\Table::TYPE_STRING, 8);
$roomTable->column('name', Swoole\Table::TYPE_STRING, 32);
$roomTable->column('ownerId', Swoole\Table::TYPE_STRING, 8); //无房主时，为空字符串
$roomTable->column('status', Swoole\Table::TYPE_INT); //当前状态
$roomTable->column('wordGroupType', Swoole\Table::TYPE_STRING, 32); //词库类型
$roomTable->column('wordGroupId', Swoole\Table::TYPE_STRING, 16); //词库id
$roomTable->column('updatedAt', Swoole\Table::TYPE_INT); //活跃时间timestamp
$roomTable->create();
 
$roomTable->set('test', [
    'id' => 'test',
    'name' => '测试房间',
    'status' => 1,
    'updatedAt' => time(),
    'wordGroupType' => \App\Entity\Room::WORD_GROUP_DEFAULT,
]);

/********************************** 服务 **********************************/

$server = new \Swoole\WebSocket\Server("0.0.0.0", 9999);
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
$httpService = new \App\Service\HttpService();
$server->on('open', function (\Swoole\WebSocket\Server $server, $request) {
    echo "[{$request->fd}]握手成功\n";
});
$server->on('message', function (\Swoole\WebSocket\Server $server, $frame) use($service) {
    SocketUtil::contextSet(SocketUtil::CTX_SERVER, $server);
    SocketUtil::contextSet(SocketUtil::CTX_FD, $frame->fd);
    echo "[{$frame->fd}]原始消息:{$frame->data}\n";
    $service->onMessage($frame, $frame->fd);
});
$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use($httpService) {
    global $server;
    $httpService->response = $response;
    $response->header('Access-Control-Allow-Origin', '*');
    $response->header('Access-Control-Allow-Headers', '*');
    $response->header('Access-Control-Allow-Methods', '*');
    SocketUtil::contextSet(SocketUtil::CTX_SERVER, $server);
    if ($request->getMethod() === 'OPTIONS') {
        $httpService->success([]);
        return;
    }
    if (!$httpService->headerCheck($request)) {
        $httpService->response404();
        return;
    }
    $target = $request->get['target'] ?? null;
    switch ($target) {
        case 'roomInfo':
            $httpService->actionRoomInfo($request, $response);
            return;
    }
    $response->setStatusCode(404);
    $response->end();
});
$server->on('close', function (\Swoole\WebSocket\Server $server, $fd) use($service) {
    $wsStatus = $server->connection_info($fd)['websocket_status'];
    if ($wsStatus !== 3) return;
    echo "[{$fd}]客户端关闭\n";
    $service->onFdClose($fd);
});


//function checkAll($server) {
//    SocketUtil::contextSet(SocketUtil::CTX_SERVER, $server);
//    echo "状态检查\n";
//    echo \App\Utils\TableUtil::genTableStats();
//    echo "\n";
//}
//\Swoole\Timer::after(1000, function() use($server) {
//    checkAll($server);
//});
//\Swoole\Timer::tick(30 * 1000, function() use($server) {
//    checkAll($server);
//});




$server->start();
