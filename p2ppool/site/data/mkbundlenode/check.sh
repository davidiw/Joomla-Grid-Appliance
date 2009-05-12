#!/bin/bash
path=`dirname $0`
pool=`cat $path/pool`
running=`ps uax | grep $pool | grep -v grep`
if [[ ! "$running" ]]; then
  echo "NOT RUNNING!"
fi
