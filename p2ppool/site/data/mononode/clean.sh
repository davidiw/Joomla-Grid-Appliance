#!/bin/bash
path=`dirname $0`
pool=`cat $path/pool`
pid=`ps uax | grep $pool.config | grep -v grep | awk '{print $2}'`
if [[ "$pid" ]]; then
  kill -INT $pid
fi
