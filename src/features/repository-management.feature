Feature: Repository management

  Scenario: Tokens can be added for owners
    Given no token exists for user 'user-w'
    When a token added event for user 'user-w' with token 'xxxx' is published
    Then user 'user-w' has token 'xxxx'

  Scenario: Tokens can be removed
    Given a token exists for user 'user-g'
    When the token for user 'user-g' is removed
    Then user 'user-g' does not have a token

  Scenario: Repository updates can be scheduled
    Given no schedules exist for repository 'trial/and-error'
    When a repository configured event for repository 'trial/and-error' with owner 'user-y' is published
    And wait '1' second
    Then repository 'trial/and-error' has a schedule

  Scenario: Repository updates can be unscheduled
    Given a schedule exists for repository 'trial/and-no-error' with owner 'user-z'
    When a repository un-configured event for repository 'trial/and-no-error' with owner 'user-z' is published
    And wait '1' second
    Then repository 'trial/and-no-error' does not have a schedule
