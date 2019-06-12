#!/usr/bin/env bash

JENKINS_HOST="your host here"
JENKINS_PORT="your port here"

eval `ssh-agent -s`
ssh-add ~/.ssh/google_compute_engine

curl -s "http://$JENKINS_HOST:$JENKINS_PORT/overallLoad/api/json?pretty=true&depth=2" > /tmp/jenkins-output.json

killall ssh-agent
