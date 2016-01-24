#!/usr/bin/env bash

url=$1
language=$2
owner=timothy-r

./send-event.php repo-mon.repository.added $url $owner $language
./send-event.php repo-mon.repository.activated $url $owner $language
./send-event.php repo-mon.update.scheduled $url $owner $language
