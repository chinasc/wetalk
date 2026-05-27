<?php
namespace app\service;

use Channel\Client;
use Workerman\Connection\TcpConnection;

class WsService
{
    public static array $connections = [];

    public static function bind(int $userId, TcpConnection $connection): void
    {
        // Kick existing connection for same user
        if (isset(self::$connections[$userId])) {
            $old = self::$connections[$userId];
            $old->send(json_encode([
                'event' => 'kicked',
                'data' => ['msg' => '您的账号已在其他设备登录'],
            ], JSON_UNESCAPED_UNICODE));
            $old->close();
            unset(self::$connections[$userId]);
        }

        self::$connections[$userId] = $connection;
        \support\Redis::set('user_online:' . $userId, 1);
        \support\Redis::set('user_fd:' . $userId, $connection->id);
    }

    public static function unbind(int $userId): void
    {
        unset(self::$connections[$userId]);
        \support\Redis::del('user_online:' . $userId);
        \support\Redis::del('user_fd:' . $userId);
    }

    public static function sendToUser(int $userId, array $data): bool
    {
        // Direct send if connection is in this process
        if (isset(self::$connections[$userId])) {
            self::$connections[$userId]->send(json_encode($data, JSON_UNESCAPED_UNICODE));
            return true;
        }

        // Cross-process: publish via Channel, WS worker delivers
        try {
            Client::connect('127.0.0.1', 2206);
            Client::publish('ws_send', json_encode([
                'user_id' => $userId,
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // Channel unavailable — message still saved in DB, user can fetch on reconnect
            return false;
        }
        return false;
    }

    public static function onChannelMessage(string $payload): void
    {
        $msg = json_decode($payload, true);
        if (!$msg || !isset($msg['user_id'], $msg['data'])) {
            return;
        }
        $userId = (int)$msg['user_id'];
        if (isset(self::$connections[$userId])) {
            self::$connections[$userId]->send(
                json_encode($msg['data'], JSON_UNESCAPED_UNICODE)
            );
        }
    }

    public static function isOnline(int $userId): bool
    {
        return (bool)\support\Redis::get('user_online:' . $userId);
    }
}
