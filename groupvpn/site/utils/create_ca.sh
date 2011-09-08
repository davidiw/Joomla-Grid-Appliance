#!/bin/bash
chmod +x $0
path=`which $0`
path=`dirname $path`
private_path="../private/"$3

cd $path &> /dev/null
# replace the following entries with information about yourself
mono certhelper.exe makecert country=USA organization="$3" organizational_unit="NA" name="$1" email="$2" node_address="none_I_am_CA\!" outkey="$private_path"/cakey outcert="$private_path"/cacert.tmp
mono certhelper.exe signcert incert="$private_path"/cacert.tmp outcert="$private_path"/cacert cakey="$private_path"/cakey cacert="$private_path"/cacert.tmp
rm "$private_path"/cacert.tmp
cd - &> /dev/null
