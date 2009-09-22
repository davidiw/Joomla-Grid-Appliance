#!/bin/bash
chmod +x $0
path=`which $0`
path=`dirname $path`

export LD_LIBRARY_PATH=/lib:/usr/lib:/usr/local/lib:$path
cd $path
export MONO_NO_SMP=1

pool=`cat $path/pool`
if [[ -e $path/basicnode ]]; then
  app="basicnode"
elif [[ -e $path/p2pnode ]]; then
  app="p2pnode -n "
fi

chmod +x $path/$app
chmod +x $path/cronolog

nohup $path/$app $path/$pool.config 2>&1 | $path/cronolog --period="1 day" $path/node.log.%y%m%d.txt &
sleep 2
