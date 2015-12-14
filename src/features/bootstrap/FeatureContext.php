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

    private $scheduler_host = 'scheduler';

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
     * @Given no schedules exist for repository :arg1
     */
    public function noSchedulesExistForRepository($repository)
    {
        $client = new Client();

        $endpoint = sprintf('http://%s/schedules/%s', $this->scheduler_host, $repository);

        $client->request('DELETE', $endpoint);
    }

    /**
     * @When a repository configured event for repository :arg1 with owner :arg2 is published
     */
    public function aRepositoryConfiguredEventForRepositoryWithOwnerIsPublished($repository, $owner)
    {
        $this->publishEvent(
            [
                'name' => 'repo-mon.repo.configured',
                'data' => [
                    'owner' => $owner,
                    'url' => $repository,
                    'language' => 'PHP7',
                    'dependency_manager' => 'composer',
                    'frequency' => '1',
                    'hour' => '1',
                    'timezone' => 'UTC',
                ]
            ]
        );
    }

    /**
     * @Then repository :arg1 has a schedule
     */
    public function repositoryHasASchedule($repository)
    {
        $client = new Client();

        $endpoint = sprintf('http://%s/schedules/%s', $this->scheduler_host, $repository);

        $schedules = json_decode($client->request('GET', $endpoint)->getBody(), true);

        foreach ($schedules as $scheduled_repository){
            if ($repository === $scheduled_repository['name']){
                // found it
                return;
            }
        }

        throw new Exception(
            "Expected repository '$repository' to be scheduled. " . print_r($schedules)
        );
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
     * @param array $event
     */
    private function publishEvent(array $event)
    {
        $this->connect();

        $msg = new AMQPMessage(json_encode($event, JSON_UNESCAPED_SLASHES), [
            'content_type' => 'application/json',
            'timestamp' => time()
        ]);

        $this->channel->basic_publish($msg, $this->rabbit_channel);

    }
}
