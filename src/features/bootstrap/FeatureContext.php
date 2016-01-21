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

    private $repository_host = 'repository';
    
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
        $this->addToken($user, $token);
    }

    /**
     * @Given no token exists for user :arg1
     */
    public function noTokenExistsForUser($user)
    {
        $this->removeToken($user);
    }

    /**
     * @Given the token for user :arg1 is removed
     */
    public function theTokenForUserIsRemoved($user)
    {
        $this->removeToken($user);
    }

    private function addToken($user, $token)
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

    private function removeToken($user)
    {
        $this->publishEvent(
            [
                'name' => 'repo-mon.token.removed',
                'data' => [
                    'user' => $user
                ]
            ]
        );
    }

    /**
     * @Given a token exists for user :arg1
     */
    public function aTokenExistsForUser($user)
    {
        $this->addToken($user, 'abcd1234');
    }

    /**
     * @Then user :arg1 has token :arg2
     */
    public function userHasToken($user, $token)
    {
        $actual = $this->getUserToken($user);

        if ($token !== $actual) {
            throw new Exception(
                "Expected token to be '$token' but it is '$actual'"
            );
        }
    }

    /**
     * @Then user :arg1 does not have a token
     */
    public function userDoesNotHaveAToken($user)
    {
        $actual = $this->getUserToken($user);

        if (!is_null($actual)){
            throw new Exception(
                "Did not expect a token to exist of user '$user"
            );
        }
    }

    /**
     * @Given a schedule exists for repository :arg1 with owner :arg2
     */
    public function aScheduleExistsForRepository($repository, $owner)
    {
        $this->anEventForRepositoryWithOwnerIsPublished('repo-mon.repository.activated', $repository, $owner);
    }

    /**
     * @Given no schedules exist for repository :arg1 with owner :arg2
     */
    public function noSchedulesExistForRepository($repository, $owner)
    {
        $this->anEventForRepositoryWithOwnerIsPublished('repo-mon.repository.deactivated', $repository, $owner);
    }

    /**
     * @When a :arg1 event for repository :arg2 with owner :arg3 is published
     */
    public function anEventForRepositoryWithOwnerIsPublished($event, $repository, $owner)
    {
        $this->publishEvent(
            [
                'name' => $event,
                'data' => [
                    'owner' => $owner,
                    'url' => 'https://github.com/'.$repository,
                    'description' => 'A repository called ' . $repository,
                    'full_name' => $repository,
                    'language' => 'PHP',
                    'dependency_manager' => 'composer',
                    'frequency' => '1',
                    'hour' => '1',
                    'timezone' => 'Europe/London',
                    'private' => false,
                ]
            ]
        );
    }

    /**
     * @Then repository :arg1 has a schedule
     */
    public function assertRepositoryHasASchedule($repository)
    {
        $client = new Client();

        $endpoint = sprintf('http://%s/schedules/%s', $this->scheduler_host, $repository);

        $schedules = json_decode($client->request('GET', $endpoint)->getBody(), true);

        foreach ($schedules as $scheduled_repository){
            if ($repository === $scheduled_repository['full_name']){
                // found it
                return;
            }
        }

        throw new Exception(
            "Expected repository '$repository' to be scheduled. " . print_r($schedules, 1)
        );
    }

    /**
     * @Then repository :arg1 with owner :arg2 is available
     */
    public function assertRepositoryIsAvailable($repository, $owner)
    {
        $repositories = $this->getAvailableRepositoriesForOwner($owner);

        foreach ($repositories as $available_repository){
            if ($repository === $available_repository['full_name']){
                // found it
                return;
            }
        }

        throw new Exception(
            "Expected repository '$repository' to be available. " . print_r($repositories, 1)
        );
    }

    /**
     * @Then repository :arg1 with owner :arg2 is unavailable
     */
    public function assertRepositoryIsUnavailable($repository, $owner)
    {
        $repositories = $this->getAvailableRepositoriesForOwner($owner);

        foreach ($repositories as $available_repository){
            if ($repository === $available_repository['full_name']){
                // found it
                throw new Exception(
                    "Did not expected repository '$repository' to be available. " . print_r($repositories, 1)
                );
            }
        }
    }

    /**
     * @Then repository :arg1 with owner :arg2 is activated
     */
    public function assertRepositoryIsActivated($repository, $owner)
    {
        $repositories = $this->getAvailableRepositoriesForOwner($owner);

        foreach ($repositories as $available_repository){
            if (($repository === $available_repository['full_name']) && ($available_repository['active'] == 1)) {
                // found it
                return;
            }
        }

        throw new Exception(
            "Expected repository '$repository' to be activated. " . print_r($repositories, 1)
        );
    }


    /**
     * @Then repository :arg1 with owner :arg2 is deactivated
     */
    public function assertRepositoryIsDeactivated($repository, $owner)
    {
        $repositories = $this->getAvailableRepositoriesForOwner($owner);

        foreach ($repositories as $available_repository){
            if (($repository === $available_repository['full_name']) && ($available_repository['active'] == 0)) {
                // found it
                return;
            }
        }

        throw new Exception(
            "Expected repository '$repository' to be deactivated. " . print_r($repositories, 1)
        );
    }

    /**
     * @Then a request for the token of user :arg1 fails
     */
    public function aRequestForTheTokenOfUserFails($user)
    {
        $actual = $this->getUserToken($user);

        if (!is_null($actual)){
            throw new Exception(
                "Did not expect a token to exist of user '$user"
            );
        }
    }

    /**
     * @Then repository :arg1 does not have a schedule
     */
    public function assertRepositoryDoesNotHaveASchedule($repository)
    {
        $client = new Client();

        $endpoint = sprintf('http://%s/schedules/%s', $this->scheduler_host, $repository);

        try {
            $schedules = json_decode($client->request('GET', $endpoint)->getBody(), true);
            throw new Exception(
                "Did not expect repository '$repository' to be scheduled. " . print_r($schedules, 1)
            );
        } catch (Exception $ex) {

        }

    }

    /**
     * @Then wait :arg1 second
     */
    public function waitForXSeconds($seconds)
    {
        sleep($seconds);
    }

    /**
     * @param string $owner
     * @return array
     */
    private function getAvailableRepositoriesForOwner($owner)
    {
        $client = new Client();

        $endpoint = sprintf('http://%s/repositories?owner=%s', $this->repository_host, $owner);

        return json_decode($client->request('GET', $endpoint)->getBody(), true);
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
        $event['version'] = '1.0.0';

        $this->connect();

        $msg = new AMQPMessage(json_encode($event, JSON_UNESCAPED_SLASHES), [
            'content_type' => 'application/json',
            'timestamp' => time()
        ]);

        $this->channel->basic_publish($msg, $this->rabbit_channel);

    }

    /**
     * @param $user
     * @return null|string
     */
    private function getUserToken($user)
    {
        $client = new Client();

        $endpoint = sprintf('http://%s/tokens/%s', $this->token_host, $user);

        try {
            return trim($client->request('GET', $endpoint)->getBody());
        } catch (Exception $ex) {
            return null;
        }
    }
}
