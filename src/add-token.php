#!/usr/bin/env php
<?php

require_once(__DIR__.'/vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

$rabbit_host = 'rabbitmq';
$rabbit_port = '5672';
$channel_name = 'repo-mon.main';

$connection = new AMQPStreamConnection($rabbit_host, $rabbit_port, 'guest', 'guest');
$channel = $connection->channel();
$channel->exchange_declare($channel_name, 'fanout', false, false, false);

$user = $argv[1];
$token = $argv[2];

$event = [
    'name' => 'repo-mon.token.added',
    'data' => [
        'user' => $user,
        'token' => $token,
    ]
];

$msg = new AMQPMessage(json_encode($event, JSON_UNESCAPED_SLASHES), [
    'content_type' => 'application/json',
    'timestamp' => time()
]);

$channel->basic_publish($msg, $channel_name);



