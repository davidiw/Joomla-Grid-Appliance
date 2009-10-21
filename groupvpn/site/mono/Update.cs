using CookComputing.XmlRpc;

class UpdateRevocationLists {
  public static void Main()
  {
    IGroupVPNServer group_vpn = (IGroupVPNServer) XmlRpcProxyGen.Create(typeof(IGroupVPNServer));
    group_vpn.Url = "http://127.0.0.1/components/com_groupvpn/mono/GroupVPN.rem";
    group_vpn.UpdateRevocationLists();
  }

  public interface IGroupVPNServer : IXmlRpcProxy {
    [XmlRpcMethod]
    bool UpdateRevocationLists();
//    bool UpdateRevocationList(string group_name);
  }
}
