#!/bin/bash
chmod +x $0
path=`which $0`
path=`dirname $path`

pool=`cat $path/pool`
running=`ps uax | grep $pool | grep -v grep`
if [[ ! "$running" ]]; then
  echo "NOT RUNNING!"
fi
