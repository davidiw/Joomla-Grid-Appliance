#!/bin/bash
FPATH=$1
for i in `find $FPATH`; do
  if test -d $i; then
    continue
  fi
  val=`echo $i | grep -oE "[^\.]+[^/]+"`
  echo "    <filename>"$val"</filename>"
done
