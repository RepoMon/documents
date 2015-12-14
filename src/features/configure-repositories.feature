Feature: Configure repositories

Scenario: Tokens can be added for owners
  Given no token exists for user 'user-w'
  When a token added event for user 'user-w' with token 'xxxx' is published
  Then user 'user-w' has token 'xxxx'

Scenario: Repository updates can be scheduled
  Given no schedules exist for repository 'trial/and-error'
  When a repository configured event for repository 'trial/and-error' with owner 'user-y' is published
  Then repository 'trial/and-error' has a schedule

Scenario: Repository updates can be unscheduled
  Given a schedule exists for repository 'trial/and-error' with owner 'user-z'
  When a repository un-configured event for repository 'trial/and-error' with owner 'user-z' is published
  Then repository 'trial/and-error' does not have a schedule
