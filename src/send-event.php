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

$name = $argv[1];
$url = $argv[2];
$owner = $argv[3];

$full_name = trim(parse_url($url, PHP_URL_PATH), '/');

$event = [
    'name' => $name,
    'data' => [
        'owner' => $owner,
        'url' => $url,
        'description' => 'A repository called ' . $full_name,
        'full_name' => $full_name,
        'language' => 'PHP',
        'dependency_manager' => 'composer',
        'frequency' => '1',
        'hour' => '1',
        'timezone' => 'Europe/London',
        'private' => false
    ]
];

$msg = new AMQPMessage(json_encode($event, JSON_UNESCAPED_SLASHES), [
    'content_type' => 'application/json',
    'timestamp' => time()
]);

$channel->basic_publish($msg, $channel_name);



