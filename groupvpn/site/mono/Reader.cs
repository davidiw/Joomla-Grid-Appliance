using Brunet;
using Mono.Security.X509;
using System;
using System.Collections;
using System.IO;
using System.Security.Cryptography;

class RevocationListReader {
  public static void Main(string[] args)
  {
    string rl_path = args[0];
    string cacert_path = args[1];

    byte[] data = null;
    using(FileStream fs = File.Open(rl_path, FileMode.Open)) {
      data = new byte[fs.Length];
      fs.Read(data, 0, data.Length);
    }

    Certificate cert = null;
    using(FileStream fs = File.Open(cacert_path, FileMode.Open)) {
      byte[] cert_blob = new byte[fs.Length];
      fs.Read(cert_blob, 0, cert_blob.Length);
      cert = new Certificate(cert_blob);
    }

    int length = data.Length;
    if(length < 12) {
      Console.WriteLine("No data?  Didn't get enough data...");
      return;
    }

    length = NumberSerializer.ReadInt(data, 0);
    DateTime date = new DateTime(NumberSerializer.ReadLong(data, 4));
    if(date < DateTime.UtcNow.AddHours(-24)) {
      Console.WriteLine("Revocation list is over 24 hours old");
    }

    Console.WriteLine("CRL Date: " + date);

    if(length > data.Length - 12) {
      Console.WriteLine("Missing data?  Didn't get enough data...");
      return;
    }

    SHA1CryptoServiceProvider sha1 = new SHA1CryptoServiceProvider();
    byte[] hash = sha1.ComputeHash(data, 4, length);
    byte[] signature = new byte[data.Length - 4 - length];
    Array.Copy(data, 4 + length, signature, 0, signature.Length);

    if(!cert.PublicKey.VerifyHash(hash, CryptoConfig.MapNameToOID("SHA1"), signature)) {
      Console.WriteLine("Invalid signature!");
      return;
    }

    MemBlock mem = MemBlock.Reference(data, 12, length - 8);

    object list = null;
    try {
      list = AdrConverter.Deserialize(mem) as ArrayList;
    } catch {
      Console.WriteLine("Unable to deserialize data...");
    }

    ArrayList rl = list as ArrayList;
    if(rl == null) {
      Console.WriteLine("Data wasn't a list...");
      return;
    }

    foreach(string name in rl) {
      Console.WriteLine(name);
    }
  }
}
