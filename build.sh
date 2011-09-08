#!/bin/bash
files="p2ppool
groupappliances
groupvpn
"

for file in $files; do
  mkdir -p tmp/$file
  cp -axf $file/* tmp/$file/.
  cd tmp
  zip -r9 ../$file.zip $file
  cd -
done

rm -rf tmp
