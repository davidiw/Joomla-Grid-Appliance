#!/bin/bash
chmod +x $0
path=`which $0`
path=`dirname $path`
private_path="../private/"$1
cd $path
mono certhelper.exe signcert incert="$private_path"/$2 outcert="$private_path"/$2.signed cakey="$private_path"/cakey cacert="$private_path"/cacert
cd -
