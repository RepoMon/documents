Feature: Configure repositories for management

Scenario: Tokens can be added for owners
  Given no token exists for user 'user-w'
  When a token added event for user 'user-w' with token 'xxxx' is published
  Then user 'user-w' has token 'xxxx'

Scenario: Repositories can be added
