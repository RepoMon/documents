<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
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
     * @Given event named :arg1 for user :arg2 with token :arg3 is published
     */
    public function eventNamedForUserWithTokenIsPublished($event, $user, $token)
    {

        throw new PendingException();
    }

    /**
     * @When I make a request for the token of user :arg1
     */
    public function iMakeARequestForTheTokenOfUser($user)
    {
        throw new PendingException();
    }

    /**
     * @Then I receive token :arg1
     */
    public function iReceiveToken($token)
    {
        throw new PendingException();
    }
}
