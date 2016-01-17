#!/usr/bin/env bash

url=$1
owner=timothy-r

./send-event.php repo-mon.repository.added $url $owner
./send-event.php repo-mon.repository.activated $url $owner
./send-event.php repo-mon.update.scheduled $url $owner
