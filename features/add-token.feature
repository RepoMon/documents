Feature: Configure repositories for management

Scenario: Tokens can be added for owners
  Given event named 'repo-mon.token.added' for user 'user-w' with token 'xxxx' is published
  When I make a request for the token of user 'user-w'
  Then I receive token 'xxxx'

Scenario: Repositories can be added
