Feature: Repository management

  Scenario: Tokens can be added for owners
    Given no token exists for user 'user-w'
    When a token added event for user 'user-w' with token 'xxxx' is published
    And wait '1' second
    Then user 'user-w' has token 'xxxx'

  Scenario: Tokens can be removed
    Given a token exists for user 'user-g'
    When the token for user 'user-g' is removed
    Then user 'user-g' does not have a token

  Scenario: Repository updates can be scheduled
    Given no schedules exist for repository 'trial/and-error' with owner 'user-y'
    When a 'repo-mon.repository.activated' event for repository 'trial/and-error' with owner 'user-y' is published
    And wait '1' second
    Then repository 'trial/and-error' has a schedule

  Scenario: Repository updates can be unscheduled
    Given a schedule exists for repository 'trial/and-no-error' with owner 'user-z'
    When a 'repo-mon.repository.deactivated' event for repository 'trial/and-no-error' with owner 'user-z' is published
    And wait '1' second
    Then repository 'trial/and-no-error' does not have a schedule

  Scenario: Repositories can be added
    When a 'repo-mon.repository.added' event for repository 'test/abc' with owner 'user-a' is published
    And wait '1' second
    Then repository 'test/abc' with owner 'user-a' is available

  Scenario: Repositories can be activated
    Given a 'repo-mon.repository.activated' event for repository 'test/abc' with owner 'user-a' is published
    And wait '1' second
    Then repository 'test/abc' with owner 'user-a' is activated

  Scenario: Repositories can be deactivated
    Given a 'repo-mon.repository.deactivated' event for repository 'test/abc' with owner 'user-a' is published
    And wait '1' second
    Then repository 'test/abc' with owner 'user-a' is deactivated

  Scenario: Repositories can be removed
    Given a 'repo-mon.repository.added' event for repository 'test/abc' with owner 'user-a' is published
    And a 'repo-mon.repository.removed' event for repository 'test/abc' with owner 'user-a' is published
    And wait '1' second
    Then repository 'test/abc' with owner 'user-a' is unavailable