#!/bin/bash
path=`dirname $0`

export LD_LIBRARY_PATH=/lib:/usr/lib:/usr/local/lib:$path
cd $path
export MONO_NO_SMP=1

pool=`cat $path/pool`
nohup $path/basicnode $path/$pool.config 2>&1 | $path/cronolog --period="1 day" $path/node.log.%y%m%d.txt &
sleep 2
