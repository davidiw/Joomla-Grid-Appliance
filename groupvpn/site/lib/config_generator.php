<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class P2PConfigGenerator {
  static function defaultNodeConfigParams() {
    $params = new stdClass();
    $params->Namespace = "P2PGeneric";
    $params->EdgeListeners = array();

    $tcp = new stdClass();
    $tcp->port = "0";
    $tcp->type = "tcp";

    $udp = new stdClass();
    $udp->port = "0";
    $udp->type = "udp";

    $params->EdgeListeners[] = $tcp;
    $params->EdgeListeners[] = $udp;

    $params->RemoteTAs = array();
    $params->DevicesToBind = array();
    $params->Security = "false";
    $params->SecureEdges = "false";
    $params->KeyPath = "private_key";
    $params->CertificatePath = "certificates";
    $params->NCService = true;
    $params->NCOptimizeShortcuts = "true";
    $params->NCCheckpointing = "false";

    return $params;
  }

  static function generateNodeConfig($params) {
    $doc = new DOMDocument();
    $doc->formatOutput = true;
    $config = $doc->createElement("NodeConfig");

    $ele = $doc->createElement("BrunetNamespace");
    $ele->appendChild($doc->createTextNode($params->Namespace));
    $config->appendChild($ele);

    $types = array();

    $els = $doc->createElement("EdgeListeners");
    foreach($params->EdgeListeners as $set) {
      if(empty($set->type)) {
        continue;
      }

      if(empty($set->port)) {
        $set->port = 0;
      }

      $el = $doc->createElement("EdgeListener");

      $att = $doc->createAttribute("type");
      $att->appendChild($doc->createTextNode($set->type));
      $el->appendChild($att);

      $ele = $doc->createElement("port");
      $ele->appendChild($doc->createTextNode($set->port));
      $el->appendChild($ele);

      $els->appendChild($el);
    }
    $config->appendChild($els);

    if($params->RemoteTAs) {
      $rtas = $doc->createElement("RemoteTAs");
      foreach($params->RemoteTAs as $RemoteTA) {
        $ele = $doc->createElement("Transport");
        $ele->appendChild($doc->createTextNode($RemoteTA));
        $rtas->appendChild($ele);
      }
      $config->appendChild($rtas);
    }

    if($DevicesToBind) {
      $dtb = $doc->createElement("DevicesToBind");
      foreach($params->DevicesToBind as $Device) {
        $ele = $doc->createElement("Device");
        $ele->appendChild($doc->createTextNode($Device));
        $dtb->appendChild($ele);
      }
      $config->appendChild($dtb);
    }

    if($params->XmlRpc) {
      $xrm = $doc->createElement("XmlRpcManager");

      $ele = $doc->createElement("Enabled");
      $ele->appendChild($doc->createTextNode("true"));
      $xrm->appendChild($ele);

      $ele = $doc->createElement("Port");
      $ele->appendChild($doc->createTextNode($params->XmlRpc));
      $xrm->appendChild($ele);

      $config->appendChild($xrm);
    }

    if($params->RpcDht) {
      $dht = $doc->createElement("RpcDht");

      $ele = $doc->createElement("Enabled");
      $ele->appendChild($doc->createTextNode("true"));
      $dht->appendChild($ele);

      $ele = $doc->createElement("Port");
      $ele->appendChild($doc->createTextNode($params->XmlRpc));
      $dht->appendChild($ele);

      $config->appendChild($dht);
    }


    if($params->Security == "true") {
      $sec = $doc->createElement("Security");

      $ele = $doc->createElement("Enabled");
      $ele->appendChild($doc->createTextNode("true"));
      $sec->appendChild($ele);

      $ele = $doc->createElement("SecureEdges");
      $ele->appendChild($doc->createTextNode($params->SecureEdges));
      $sec->appendChild($ele);

      $ele = $doc->createElement("KeyPath");
      $ele->appendChild($doc->createTextNode($params->KeyPath));
      $sec->appendChild($ele);

      $ele = $doc->createElement("CertificatePath");
      $ele->appendChild($doc->createTextNode($params->CertificatePath));
      $sec->appendChild($ele);

      $config->appendChild($sec);
    }

    if($params->NCService) {
      $nc = $doc->createElement("NCService");

      $ele = $doc->createElement("Enabled");
      $ele->appendChild($doc->createTextNode("true"));
      $nc->appendChild($ele);

      $ele = $doc->createElement("OptimizeShortcuts");
      $ele->appendChild($doc->createTextNode($params->NCOptimizeShortcuts));
      $nc->appendChild($ele);

      $ele = $doc->createElement("Checkpointing");
      $ele->appendChild($doc->createTextNode($params->NCCheckpointing));
      $nc->appendChild($ele);

      $config->appendChild($nc);
    }

    $doc->appendChild($config);
    return $doc->saveXML();
  }

  static function defaultIPOPConfigParams() {
    $params = new stdClass();
    $params->Namespace = "IPGeneric";
    $params->VirtualNetworkDevice = "tapipop";
    $params->EnableMulticast = "true";
    $params->EndToEndSecurity = "false";
    $params->DHCPPort = "67";
    $params->AllowStaticAddresses = "true";

    return $params;
  }

  static function generateIPOPConfig($params) {
    $doc = new DOMDocument();
    $doc->formatOutput = true;
    $config = $doc->createElement("IpopConfig");

    $ele = $doc->createElement("IpopNamespace");
    $ele->appendChild($doc->createTextNode($params->Namespace));
    $config->appendChild($ele);

    $ele = $doc->createElement("VirtualNetworkDevice");
    $ele->appendChild($doc->createTextNode($params->VirtualNetworkDevice));
    $config->appendChild($ele);

    $ele = $doc->createElement("EnableMulticast");
    $ele->appendChild($doc->createTextNode($params->EnableMulticast));
    $config->appendChild($ele);

    $ele = $doc->createElement("EndToEndSecurity");
    $ele->appendChild($doc->createTextNode($params->EndToEndSecurity));
    $config->appendChild($ele);

    $ele = $doc->createElement("DHCPPort");
    $ele->appendChild($doc->createTextNode($params->DHCPPort));
    $config->appendChild($ele);

    $ele = $doc->createElement("AllowStaticAddresses");
    $ele->appendChild($doc->createTextNode($params->AllowStaticAddresses));
    $config->appendChild($ele);

    if($params->Hostname) {
      $ai = $doc->createElement("AddressData");
      $ele = $doc->createElement("Hostname");
      $ele->appendChild($doc->createTextNode($params->Hostname));
      $ai->appendChild($ele);
      $config->appendChild($ai);
    }

    if($params->GroupVPN) {
      $gvn = $doc->createElement("GroupVPN");

      $ele = $doc->createElement("Enabled");
      $ele->appendChild($doc->createTextNode("true"));
      $gvn->appendChild($ele);

      $ele = $doc->createElement("ServerURI");
      $ele->appendChild($doc->createTextNode($params->GroupVPN->ServerURI));
      $gvn->appendChild($ele);

      $ele = $doc->createElement("Group");
      $ele->appendChild($doc->createTextNode($params->GroupVPN->Group));
      $gvn->appendChild($ele);

      $ele = $doc->createElement("UserName");
      $ele->appendChild($doc->createTextNode($params->GroupVPN->UserName));
      $gvn->appendChild($ele);

      $ele = $doc->createElement("Secret");
      $ele->appendChild($doc->createTextNode($params->GroupVPN->Secret));
      $gvn->appendChild($ele);

      $config->appendChild($gvn);
    }

    $doc->appendChild($config);
    return $doc->saveXML();
  }

  static function defaultDHCPConfigParams() {
    $params = new stdClass();
    $params->Namespace = "IPGeneric";
    $params->BaseIP = "5.0.0.0";
    $params->Netmask = "255.255.255.0";
    $params->LeaseTime = "7200";
    $params->ReservedIPs = array();

    return $params;
  }

  static function generateDHCPConfig($params) {
    $doc = new DOMDocument();
    $doc->formatOutput = false;
    $config = $doc->createElement("DHCPConfig");

    $ele = $doc->createElement("Namespace");
    $ele->appendChild($doc->createTextNode($params->Namespace));
    $config->appendChild($ele);

    $ele = $doc->createElement("IPBase");
    $ele->appendChild($doc->createTextNode($params->BaseIP));
    $config->appendChild($ele);

    $ele = $doc->createElement("Netmask");
    $ele->appendChild($doc->createTextNode($params->Netmask));
    $config->appendChild($ele);

    $ele = $doc->createElement("LeaseTime");
    $ele->appendChild($doc->createTextNode($params->LeaseTime));
    $config->appendChild($ele);

    if(count($params->ReservedIPs) > 0) {
      $ris = $doc->createElement("ReservedIPs");
      foreach($params->ReservedIPs as $ReservedIP) {
        if(empty($ReservedIP->IPBase) || empty($ReservedIP->Netmask)) {
          continue;
        }
        $ri = $doc->createElement("ReservedIP");

        $ele = $doc->createElement("IPBase");
        $ele->appendChild($doc->createTextNode($ReservedIP->IPBase));
        $ri->appendChild($ele);

        $ele = $doc->createElement("Netmask");
        $ele->appendChild($doc->createTextNode($ReservedIP->Netmask));
        $ri->appendChild($ele);

        $ris->appendChild($ri);
      }
      $config->appendChild($ris);
    }

    $doc->appendChild($config);
    return $doc->saveXML();
  }
}
