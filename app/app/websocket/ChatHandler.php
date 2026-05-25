<?php
namespace app\websocket;

use app\model\User;
use app\service\JwtService;
use app\service\WsService;
use Channel\Client;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

class ChatHandler
{
    public function onWorkerStart(Worker $worker)
    {
        Timer::add(30, function () use ($worker) {
            foreach ($worker->connections as $connection) {
                if (isset($connection->userId)) {
                    \support\Redis::set('user_online:' . $connection->userId, 1);
                }
            }
        });

        // Subscribe to Channel: HTTP process publishes, WS worker delivers instantly
        Client::connect('127.0.0.1', 2206);
        Client::on('ws_send', function ($payload) {
            WsService::onChannelMessage($payload);
        });
    }

    public function onConnect(TcpConnection $connection)
    {
    }

    public function onMessage(TcpConnection $connection, $data)
    {
        $msg = json_decode($data, true);
        if (!$msg || !isset($msg['event'])) {
            return;
        }

        switch ($msg['event']) {
            case 'auth':
                $this->handleAuth($connection, $msg['data'] ?? []);
                break;
            case 'chat.send':
                $this->handleChatSend($connection, $msg['data'] ?? []);
                break;
            case 'chat.read':
                $this->handleChatRead($connection, $msg['data'] ?? []);
                break;
            case 'ping':
                $connection->send(json_encode(['event' => 'pong']));
                break;
        }
    }

    public function onClose(TcpConnection $connection)
    {
        if (isset($connection->userId)) {
            $userId = $connection->userId;
            WsService::unbind($userId);

            $user = User::find($userId);
            if ($user && $user->role === 'customer_service') {
                $this->broadcastCsStatus($userId, false);
            }
        }
    }

    private function handleAuth(TcpConnection $connection, array $data)
    {
        $token = $data['token'] ?? '';
        try {
            $decoded = JwtService::decode($token);
            $user = User::find($decoded->uid);
            if (!$user || $user->status !== 1) {
                $connection->send(json_encode(['event' => 'auth.fail', 'data' => ['msg' => '认证失败']]));
                return;
            }
            $connection->userId = $user->id;
            WsService::bind($user->id, $connection);
            if ($user->role === 'customer_service') {
                $this->broadcastCsStatus($user->id, true);
            }

            $connection->send(json_encode([
                'event' => 'auth.success',
                'data' => ['user_id' => $user->id, 'role' => $user->role],
            ]));
        } catch (\Exception $e) {
            $connection->send(json_encode(['event' => 'auth.fail', 'data' => ['msg' => 'Token 无效']]));
        }
    }

    private function handleChatSend(TcpConnection $connection, array $data)
    {
        if (!isset($connection->userId)) {
            return;
        }

        $conversationId = (int)($data['conversation_id'] ?? 0);
        $type = $data['type'] ?? 'text';
        $content = $data['content'] ?? '';

        if (!$conversationId || !$content) {
            return;
        }

        $conversation = \app\model\Conversation::find($conversationId);
        if (!$conversation) {
            return;
        }

        $userId = $connection->userId;
        $isUser = ($userId === $conversation->user_id);
        $isCs = ($userId === $conversation->customer_service_id);
        if (!$isUser && !$isCs) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $message = \app\model\Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $userId,
            'sender_type' => $isUser ? 'user' : 'customer_service',
            'type' => $type,
            'content' => $content,
            'created_at' => $now,
        ]);

        $conversation->touch();

        $user = User::find($userId);
        $targetId = $isUser ? $conversation->customer_service_id : $conversation->user_id;
        WsService::sendToUser($targetId, [
            'event' => 'message.new',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $conversationId,
                'sender_id' => $userId,
                'sender_type' => $message->sender_type,
                'sender_nickname' => $user->nickname,
                'sender_avatar' => $user->avatar,
                'type' => $type,
                'content' => $content,
                'created_at' => $now,
            ],
        ]);
    }

    private function handleChatRead(TcpConnection $connection, array $data)
    {
        if (!isset($connection->userId)) {
            return;
        }

        $conversationId = (int)($data['conversation_id'] ?? 0);
        if (!$conversationId) {
            return;
        }

        $conversation = \app\model\Conversation::find($conversationId);
        if (!$conversation) {
            return;
        }

        $userId = $connection->userId;
        $targetId = ($userId === $conversation->user_id)
            ? $conversation->customer_service_id
            : $conversation->user_id;

        WsService::sendToUser($targetId, [
            'event' => 'message.read',
            'data' => [
                'conversation_id' => $conversationId,
                'reader_id' => $userId,
            ],
        ]);
    }

    private function broadcastCsStatus(int $csId, bool $online)
    {
        $user = User::find($csId);
        $event = $online ? 'cs.online' : 'cs.offline';
        $data = ['event' => $event, 'data' => ['cs_id' => $csId, 'nickname' => $user->nickname ?? '']];
        foreach (WsService::$connections as $uid => $conn) {
            $conn->send(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }
}
