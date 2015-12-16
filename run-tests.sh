#!/usr/bin/env bash

# run the tests in the test container
# assuming it's running using the docker-composer config file

docker exec -ti repo_documents_1 ./bin/behat features/