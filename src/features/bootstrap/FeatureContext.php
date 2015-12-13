<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use GuzzleHttp\Client;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    private $token_host = 'token';

    private $rabbit_host = 'rabbitmq';

    private $rabbit_port = '5672';

    private $rabbit_channel = 'repo-mon.main';

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }


    /**
     * @Given a token added event for user :arg1 with token :arg2 is published
     */
    public function aTokenAddedEventForUserWithTokenIsPublished($user, $token)
    {
        $this->publishEvent(
            [
                'name' => 'repo-mon.token.added',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ]
        );
    }

    /**
     * @Given no token exists for user :arg1
     */
    public function noTokenExistsForUser($user)
    {
        $client = new Client();

        // trim any white space from the response body
        $endpoint = sprintf('http://%s/tokens/%s', $this->token_host, $user);

        $client->request('DELETE', $endpoint);
    }

    /**
     * @Then user :arg1 has token :arg2
     */
    public function userHasToken($user, $token)
    {
        $client = new Client();

        // trim any white space from the response body
        $endpoint = sprintf('http://%s/tokens/%s', $this->token_host, $user);

        $actual = trim($client->request('GET', $endpoint)->getBody());

        if ($token !== $actual) {
            throw new Exception(
                "Expected token to be '$token' but it is '$actual'"
            );
        }
    }

    /**
     *
     */
    private function connect()
    {
        if (!$this->connection) {
            $this->connection = new AMQPStreamConnection($this->rabbit_host, $this->rabbit_port, 'guest', 'guest');
            $this->channel = $this->connection->channel();
            $this->channel->exchange_declare($this->rabbit_channel, 'fanout', false, false, false);
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->connection) {
            $this->channel->close();
            $this->connection->close();
        }
    }

    /**
     * @param array $event
     */
    public function publishEvent(array $event)
    {
        $this->connect();

        $msg = new AMQPMessage(json_encode($event, JSON_UNESCAPED_SLASHES), [
            'content_type' => 'application/json',
            'timestamp' => time()
        ]);

        $this->channel->basic_publish($msg, $this->rabbit_channel);

    }
}
