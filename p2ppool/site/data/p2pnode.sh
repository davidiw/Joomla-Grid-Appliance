#!/bin/bash
chmod +x $0
install_path=$2
path=$2/node
user=`whoami`

if test -d $path; then
  if test -f $path/pool; then
    pool=`cat $path/pool 2> /dev/null`
  fi
fi

function check()
{
  if test ! "$pool"; then
    echo "Failed!"
    exit -1
  fi

  expected_ns=$1
  if test -f $path/$pool.config; then
    ns=`grep -oE 'BrunetNamespace>.+</BrunetNamespace' $path/$pool.config 2> /dev/null \
      | grep -oE '[>][^<>]+[<]' 2> /dev/null \
      | grep -oE '[^<>]+' 2> /dev/null`
  fi

  if [[ $expected_ns != $ns ]]; then
    echo "Failed!"
    exit -1
  fi

  if [[ `ps uax | grep $pool.config | grep -v grep | wc -l` != 1 ]]; then
    stop
    echo "Failed!"
    exit -1
  fi

  md5sum=$2
  if [[ $md5sum != `md5sum /home/$user/$pool.tgz | awk '{print $1}'` ]]; then
    stop
    echo "Failed!"
    exit -1
  fi
}

function setup()
{
  pool=$1
  if test ! -f /home/$user/$pool.tgz; then
    echo "Failed!"
    exit -1
  fi

#  $md5sum=$1
#  $md5sum_local=`md5sum $install_path/node.tgz`
#  if [[ $md5sum != $md5sum_local ]]; then
#    echo "Failed!"
#    exit -1
#  fi

  if test -d $path; then
    remove
  fi

  mkdir -p $install_path
  tar --overwrite --overwrite-dir -zxf /home/$user/$pool.tgz -C $install_path &> /dev/null
  stop
  sleep 2
  start
}

function remove()
{
  stop
  rm -rf $install_path
}

function start()
{
  if test ! -f $path/start_node.sh; then
    echo "Failed!"
    exit -1
  fi

  bash $path/start_node.sh < /dev/null > /dev/null 2> /dev/null
}

function stop()
{
  if test ! "$pool"; then
    "Stop Failed!"
    exit -1
  fi

  for pid in `ps uax | grep $pool.config | grep -v grep | awk '{print $2}'`; do
    kill -INT $pid
  done

  sleep 2

  for pid in `ps uax | grep $pool.config | grep -v grep | awk '{print $2}'`; do
    kill -KILL $pid
  done
}

case "$1" in
  check)
    check ${@:3}
    ;;
  install)
    install ${@:3}
    ;;
  remove)
    remove ${@:3}
    ;;
  setup)
    setup ${@:3}
    ;;
  start)
    start ${@:3}
    ;;
  stop)
    stop ${@:3}
    ;;
  *)
    echo "usage: check, remove, start, stop"
    ;;
esac
exit 0
