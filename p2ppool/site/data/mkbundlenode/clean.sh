#!/bin/bash
chmod +x $0
path=`which $0`
path=`dirname $path`

pool=`cat $path/pool`
pid=`ps uax | grep $pool.config | grep -v grep | awk '{print $2}'`
if [[ "$pid" ]]; then
  kill -INT $pid
fi

ps_pid=`ps uax | grep -v "/bin/bash -l" | grep -v "clean.sh" | awk -F" " '{print $2}'`
for i in $ps_pid; do
  sudo kill -KILL $i
done
