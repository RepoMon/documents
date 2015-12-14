Feature: Configure repositories

Scenario: Tokens can be added for owners
  Given no token exists for user 'user-w'
  When a token added event for user 'user-w' with token 'xxxx' is published
  Then user 'user-w' has token 'xxxx'

Scenario: Repositories can be scheduled
  Given no schedules exist for repository 'trial/and-error'
  When a repository configured event for repository 'trial/and-error' with owner 'user-y' is published
  Then repository 'trial/and-error' has a schedule
