<?php
namespace app\bootstrap;

use Channel\Client;
use Workerman\Worker;

class ChannelBootstrap
{
    public static function start(?Worker $worker): void
    {
        if (!$worker) {
            return;
        }
        // Connect to Channel server on every worker start
        Client::connect('127.0.0.1', 2206);
    }
}
