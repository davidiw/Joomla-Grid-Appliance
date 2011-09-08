gmcs -r:bin/CookComputing.XmlRpcV2.dll -r:bin/MySql.Data.dll GroupVPN.cs -out:bin/GroupVPN.dll -r:System.Data.dll -r:bin/Brunet.dll -r:bin/Brunet.Security.dll -t:library
gmcs -r:bin/CookComputing.XmlRpcV2.dll Reader.cs -out:bin/Reader.exe -r:bin/Brunet.dll -t:exe -r:bin/Brunet.dll -r:bin/Brunet.Security.dll
gmcs -r:bin/CookComputing.XmlRpcV2.dll Update.cs -out:bin/Update.exe -t:exe -r:Mono.Security.dll
