#!/bin/bash
chmod +x $0
path=`which $0`
echo $path
path=`dirname $path`

pool=`cat $path/pool`
pid=`ps uax | grep $pool.config | grep -v grep | awk '{print $2}'`
if [[ "$pid" ]]; then
  kill -INT $pid
fi
