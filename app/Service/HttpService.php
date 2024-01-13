<?php

namespace App\Service;

use App\Entity\Room;
use App\Entity\User;
use App\Exception\LoginException;
use App\Utils\FdControl;
use App\Utils\RoomUtil;
use App\Utils\SocketUtil;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;

class HttpService extends BaseService {
    /** @var Response */
    public $response;
    const BASE_HEADER_AUTH_KEY = 'authorization';
    public function headerCheck(Request $request): bool {
        $authRes = env('BASE_HEADER_AUTH_KEY');
        $headerRes = $request->header[self::BASE_HEADER_AUTH_KEY] ?? null;
        echo "AuthRes: $authRes \n";
        echo "HeaderRes: $headerRes \n";
        return $authRes === $headerRes;
    }
    
    public function actionRoomInfo(Request $request) {
        $roomId = $request->get['roomId'] ?? null;
        $this->log("查询RoomInfo:" . $roomId);
        if (is_null($roomId)) {
            $this->response422();
            return;
        }
        $room = RoomUtil::getRoom($roomId, true);
        if (is_null($room)) {
            $this->responseElementMessage('warning', '房间不存在');
            return;
        }
        $this->success($room->infoArray());
    }

    public function success(array $data) {
        $this->responseJson([
            'status' => 'success',
            'data' => $data
        ]);
    }
    public function response422() {
        $this->response->setStatusCode(422);
        $this->response->end();
    }
    public function response404() {
        $this->response->setStatusCode(404);
        $this->response->end();
    }
    protected function responseJson(array $data, int $code = 200) {
        $this->response->setStatusCode($code);
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->end(json_encode($data));
    }
    protected function responseElementMessage(string $type, string $message) {
        $this->responseJson([
            'elementMessage' => [
                'type' => $type,
                'message' => $message
            ]
        ]);
    }
}