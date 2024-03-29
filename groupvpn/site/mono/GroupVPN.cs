using Brunet;
using CookComputing.XmlRpc;
using Mono.Security.X509;
using MySql.Data.MySqlClient;
using System;
using System.Collections;
using System.Configuration;
using System.Data;
using System.IO;
using System.Net;
using System.Reflection;
using System.Security.Cryptography;
using System.Text;
using System.Text.RegularExpressions;
using System.Web;

class GroupVPNServer : XmlRpcService {
  protected static readonly object _sync = new object();
  protected static readonly char _sep = Path.DirectorySeparatorChar;
  protected static readonly string _path =
    System.Configuration.ConfigurationSettings.AppSettings["path"].ToString() +
    _sep + "components" + _sep + "com_groupvpn";

  protected string _connection_string {
    get {
      return String.Format(
        "Server={0}; Database={1}; User ID={2}; Password={3}; Pooling=false",
        JoomlaConfigRead("host"), JoomlaConfigRead("db"), JoomlaConfigRead("user"),
        JoomlaConfigRead("password"));
    }
  }

  protected string  _db_prefix {
    get {
      return JoomlaConfigRead("dbprefix");
    }
  }

  protected string GetGroupDataPath(string group)
  {
    return _path + _sep + "data" + _sep + group + _sep;
  }

  protected string GetGroupPrivatePath(string group)
  {
    return _path + _sep + "private" + _sep + group + _sep;
  }

  [XmlRpcMethod]
  public string SubmitRequest(string username, string group, string secret,
      byte[] certificate)
  {
    IDbConnection dbcon = new MySqlConnection(_connection_string);
    dbcon.Open();
    IDbCommand dbcmd = dbcon.CreateCommand();

    string sql = "SELECT id, email FROM " + _db_prefix + "users WHERE username = \"" + username + "\"";
    dbcmd.CommandText = sql;
    IDataReader reader = dbcmd.ExecuteReader();
    if(!reader.Read()) {
      throw new Exception("Not registered on website.");
    }

    string user_id = ((int) reader["id"]).ToString();
    string email = (string) reader["email"];
    reader.Close();

    sql = "SELECT member FROM groups WHERE" +
       " group_id = (SELECT group_id FROM groupvpn WHERE group_name = \"" + group + "\")" +
       " and user_id = " + user_id + " and secret = \"" + secret + "\" and revoked = 0";
    dbcmd.CommandText = sql;
    reader = dbcmd.ExecuteReader();
    if(!reader.Read() || !"1".Equals(reader["member"].ToString())) {
      throw new Exception("Not a member of the group.");
    }

    reader.Close();

    sql = "UPDATE groupvpn SET last_update = CURRENT_TIMESTAMP WHERE group_name = \"" + group + "\"";
    dbcmd.CommandText = sql;
    reader = dbcmd.ExecuteReader();
    reader.Close();

    dbcmd.Dispose();
    dbcon.Close();

    CertificateMaker cm = null;
    try {
      cm = new CertificateMaker(certificate);
    } catch {
      throw new Exception("Invalid certificate request");
    }

    cm = new CertificateMaker(string.Empty, string.Empty, group, username,
        email, cm.PublicKey, cm.NodeAddress);

    Random rand = new Random();
    byte[] request_id_blob = new byte[20];
    rand.NextBytes(request_id_blob);

    StringBuilder request_id_sb = new StringBuilder(request_id_blob.Length);
    foreach(byte b in request_id_blob) {
      request_id_sb.Append(b.ToString("X2"));
    }
    string request_id = request_id_sb.ToString();

    string request_path = GetGroupDataPath(group) + request_id;
    using(FileStream fs = File.Open(request_path, FileMode.Create)) {
      fs.Write(cm.UnsignedData, 0, cm.UnsignedData.Length);
    }

    // If we don't want to verify request on the website...
    SignCertificate(group, request_id);

    return request_id;
  }

  protected bool SignCertificate(string group, string request_id)
  {
    string request_path = GetGroupDataPath(group) + request_id;
    if(!File.Exists(request_path)) {
      throw new Exception("No such request.");
    }

    CertificateMaker cm = null;
    using(FileStream fs = File.Open(request_path, FileMode.Open)) {
      byte[] blob = new byte[fs.Length];
      fs.Read(blob, 0, blob.Length);
      cm = new CertificateMaker(blob);
    }
    // We need to create a new certificate with all the users info!

    string private_path = GetGroupPrivatePath(group) + "private_key";
    if(!File.Exists(private_path)) {
      throw new Exception("No private key.");
    }

    RSACryptoServiceProvider private_key = new RSACryptoServiceProvider();
    using(FileStream fs = File.Open(private_path, FileMode.Open)) {
      byte[] blob = new byte[fs.Length];
      fs.Read(blob, 0, blob.Length);
      private_key.ImportCspBlob(blob);
    }

    string cacert_path = GetGroupDataPath(group) + "cacert";
    if(!File.Exists(cacert_path)) {
      throw new Exception("No CA Certificate.");
    }

    Certificate cacert = null;
    using(FileStream fs = File.Open(cacert_path, FileMode.Open)) {
      byte[] blob = new byte[fs.Length];
      fs.Read(blob, 0, blob.Length);
      cacert = new Certificate(blob);
    }

    Certificate cert = cm.Sign(cacert, private_key);

    request_path += ".signed";
    using(FileStream fs = File.Open(request_path, FileMode.Create)) {
      byte[] blob = cert.X509.RawData;
      fs.Write(blob, 0, blob.Length);
    }

    return true;
  }

  [XmlRpcMethod]
  public byte[] CheckRequest(string group, string request_id)
  {
    string request_path = _path + _sep + "data" + _sep + group + _sep + request_id;
    if(!File.Exists(request_path)) {
      throw new Exception("No such request.");
    }

    string request_denied_path = request_path + ".denied";
    if(File.Exists(request_denied_path)) {
      throw new Exception("Request denied.");
    }

    string request_signed_path = request_path + ".signed";
    if(!File.Exists(request_signed_path)) {
      throw new Exception("Request not signed, yet.");
    }

    using(FileStream fs = File.Open(request_signed_path, FileMode.Open)) {
      byte[] blob = new byte[fs.Length];
      fs.Read(blob, 0, blob.Length);
      return blob;
    }
  }

  [XmlRpcMethod]
  public bool GenerateCACert(string group)
  {
    if(!Context.Request.IsLocal) {
      throw new Exception("Call must be made locally!");
    }

    string private_path = GetGroupPrivatePath(group);
    Directory.CreateDirectory(private_path);

    private_path += "private_key";
    RSACryptoServiceProvider private_key = new RSACryptoServiceProvider(2048);
    byte[] private_blob = private_key.ExportCspBlob(true);
    using(FileStream fs = File.Open(private_path, FileMode.Create)) {
      fs.Write(private_blob, 0, private_blob.Length);
    }

    string data_path = GetGroupDataPath(group);
    Directory.CreateDirectory(data_path);

    RSACryptoServiceProvider public_key = new RSACryptoServiceProvider();
    public_key.ImportCspBlob(private_key.ExportCspBlob(false));

    CertificateMaker cm = new CertificateMaker(string.Empty, group,
        string.Empty, "admin", string.Empty, public_key, string.Empty);
    Certificate cert = cm.Sign(cm, private_key);

    string cacert_path = GetGroupDataPath(group) + "cacert";
    byte[] cert_data = cert.X509.RawData;
    using(FileStream fs = File.Open(cacert_path, FileMode.Create)) {
      fs.Write(cert_data, 0, cert_data.Length);
    }

    return true;
  }

  [XmlRpcMethod]
  public bool UpdateRevocationLists()
  {
    if(!Context.Request.IsLocal) {
      throw new Exception("Call must be made locally!");
    }

    IDbConnection dbcon = new MySqlConnection(_connection_string);
    dbcon.Open();
    IDbCommand dbcmd = dbcon.CreateCommand();

    // Get the group_id
    string sql = "SELECT group_name FROM groupvpn";
    dbcmd.CommandText = sql;
    IDataReader reader = dbcmd.ExecuteReader();

    ArrayList groups = new ArrayList();
    while(reader.Read()) {
      groups.Add(reader["group_name"] as string);
    }

    reader.Close();
    dbcmd.Dispose();
    dbcon.Close();

    string exceptions = string.Empty;
    foreach(string group_name in groups) {
      try {
        UpdateRevocationList(group_name);
      } catch(Exception e) {
        exceptions += e.Message + "\n";
      }
    }
    
    if(exceptions != string.Empty) {
      throw new Exception(exceptions);
    }

    return true;
  }

  [XmlRpcMethod]
  public bool UpdateRevocationList(string group_name)
  {
    if(!Context.Request.IsLocal) {
      throw new Exception("Call must be made locally!");
    }

    IDbConnection dbcon = new MySqlConnection(_connection_string);
    dbcon.Open();
    IDbCommand dbcmd = dbcon.CreateCommand();

    // Get the group_id
    string sql = "SELECT group_id from groupvpn WHERE group_name = \"" + group_name + "\"";
    dbcmd.CommandText = sql;
    IDataReader reader = dbcmd.ExecuteReader();
    if(!reader.Read()) {
      throw new Exception("No such group.");
    }

    int group_id = (int) reader["group_id"];
    reader.Close();

    // get revoked users
    sql = "SELECT user_id FROM groups WHERE group_id = \"" + group_id + "\" and revoked = 1";
    dbcmd.CommandText = sql;
    reader = dbcmd.ExecuteReader();

    // add revoked users by user name to the revocation list
    ArrayList revoked_user_ids = new ArrayList();
    while(reader.Read()) {
      revoked_user_ids.Add((int) reader["user_id"]);
    }

    reader.Close();

    ArrayList revoked_users = new ArrayList();
    foreach(int user_id in revoked_user_ids) {
      sql = "SELECT username FROM " + _db_prefix + "users WHERE id = " + user_id;
      dbcmd.CommandText = sql;
      IDataReader user_reader = dbcmd.ExecuteReader();
      if(!user_reader.Read()) {
        continue;
      }

      revoked_users.Add(user_reader["username"]);
      user_reader.Close();
    }

    reader.Close();
    dbcmd.Dispose();
    dbcon.Close();

    // get private key
    string private_path = GetGroupPrivatePath(group_name) + "private_key";
    if(!File.Exists(private_path)) {
      throw new Exception("No private key for " + private_path + " " + File.Exists(private_path));
    }

    RSACryptoServiceProvider private_key = new RSACryptoServiceProvider();
    using(FileStream fs = File.Open(private_path, FileMode.Open)) {
      byte[] blob = new byte[fs.Length];
      fs.Read(blob, 0, blob.Length);
      private_key.ImportCspBlob(blob);
    }

    // create revocation list
    byte[] to_sign = null;
    using(MemoryStream ms = new MemoryStream()) {
      NumberSerializer.WriteLong(DateTime.UtcNow.Ticks, ms);
      AdrConverter.Serialize(revoked_users, ms);
      to_sign = ms.ToArray();
    }

    // sign revocation list
    SHA1CryptoServiceProvider sha1 = new SHA1CryptoServiceProvider();
    byte[] hash = sha1.ComputeHash(to_sign);
    byte[] signature = private_key.SignHash(hash, CryptoConfig.MapNameToOID("SHA1"));
    byte[] data = new byte[4 + to_sign.Length + signature.Length];
    NumberSerializer.WriteInt(to_sign.Length, data, 0);
    to_sign.CopyTo(data, 4);
    signature.CopyTo(data, 4 + to_sign.Length);

    // write revocation list
    using(FileStream fs = File.Open(GetGroupDataPath(group_name) + "revocation_list", FileMode.Create)) {
      fs.Write(data, 0, data.Length);
    }

    return true;
  }

  static string JoomlaConfigRead(string name)
  {
    string path = System.Configuration.ConfigurationSettings.AppSettings["path"].ToString();
    path += _sep + "configuration.php";

    string text = null;
    using(StreamReader sr = new StreamReader(File.OpenRead(path))) {
      text = sr.ReadToEnd();
    }

    Regex line_re =  new Regex("\\$" + name + "\\s*=[^;]*");
    Regex value_re = new Regex("['][^']*[']");
    Regex no_quotes = new Regex("[^']+");

    Match match = line_re.Match(text);
    if(!match.Success) {
      return string.Empty;
    }

    string value = match.Groups[0].Captures[0].ToString();

    match = value_re.Match(value);
    if(!match.Success) {
      return string.Empty;
    }

    value = match.Groups[0].Captures[0].ToString();

    match = no_quotes.Match(value);
    if(!match.Success) {
      return string.Empty;
    }

    return match.Groups[0].Captures[0].ToString();
  }
}
