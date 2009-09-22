#!/usr/bin/python
usage = """usage:
Remoter [--path_to_files=<filename>] [--username=<username>]
  --path_to_nodes=<filename> [--ssh_key=<filename>]
  [--install_path=<path>] action
action = check, install, uninstall, gather_stats, gather_logs, uptime (check
  attempts to add the boot strap software to nodes that do not have it yet...
  a common problem on planetlab)
path_to_nodes = a file containing a new line delimited file containing hosts
  to install basic node to... optional, use plab list if unspecified.
username = the user name for the hosts
install_path = path to install the software to on the remote machine
path_to_files = the path to a downloadable file that contains the installation
  files A sample is available at ...
ssh_key = path to the ssh key to be used
"""

import os, sys, time, signal, subprocess, re, getopt, xmlrpclib, traceback

def main():
  optlist, args = getopt.getopt(sys.argv[1:], "", ["path_to_files=", \
    "username=", "path_to_nodes=", "ssh_key=", "install_path="])

  o_d = {}
  for k,v in optlist:
    o_d[k] = v

  try:
    nodes = None
    if "--path_to_nodes" in o_d:
      nodes = []
      nodes_file = o_d["--path_to_nodes"]
      f = open(nodes_file)
      line = f.readline()
      nodes.append(line.rstrip('\n\r '))
      for line in f:
        nodes.append(line.rstrip('\n\r '))
      f.close()

    action = args[0]
    username = o_d["--username"]
    ssh_key = None
    if "--ssh_key" in o_d:
      ssh_key = o_d["--ssh_key"]
    path_to_files = None
    if "--path_to_files" in o_d:
      path_to_files = o_d["--path_to_files"]
    install_path = ""
    if "--install_path" in o_d:
      install_path = o_d["--install_path"]
    plab = Remoter(action, nodes, username=username, path_to_files=path_to_files, \
        ssh_key=ssh_key, install_path=install_path)
  except:
    print_usage()

  plab.run()

def print_usage():
  print usage
  os._exit(0)

def logger_std_out(text):
  print(text)

class Remoter:
  def __init__(self, action, nodes = None, username = "", path_to_files = "", \
    update_callback = False, ssh_key=None, install_path = "", pool = "", \
    logger = logger_std_out, namespace = "default", setup_file = ""):

    self.setup_file = setup_file
    self.namespace = namespace
    self.logger = logger
    self.data_path = sys.path[0] + os.sep + ".." + os.sep + ".." + os.sep + "data" + os.sep + pool + os.sep
    os.chdir(self.data_path)
    if action == "install":
      self.task = self.install_node
    elif action == "check":
      self.task = self.check_node
    elif action == "uninstall":
      self.task = self.uninstall_node
    elif action == "gather_stats":
      self.task = self.get_stats
    elif action == "gather_logs":
      self.task = self.gather_logs
      os.system("rm -rf logs")
      os.system("mkdir logs")
    elif action == "uptime":
      self.task = self.cmd
      self.args = "uptime"
      os.system("rm -rf cmd")
      os.system("mkdir cmd")
    elif action == "ls":
      self.task = self.cmd
      self.args = "ls -al"
      os.system("rm -rf cmd")
      os.system("mkdir cmd")
    elif action == "ps":
      self.task = self.cmd
      self.args = "ps uax"
      os.system("rm -rf cmd")
      os.system("mkdir cmd")
    else:
      "Invalid action: " + action
      print_usage()

    self.action = action
    self.username = username
    self.nodes = nodes
    self.path_to_files = path_to_files
    self.update_callback = update_callback
    self.install_path = install_path
    self.pool = pool
    os.system("md5sum %s | awk '{print $1}' > %s.md5sum" % (self.path_to_files, self.path_to_files))
    self.md5sum = open(self.path_to_files + ".md5sum").read()

    ssh_ops = "-o StrictHostKeyChecking=no -o HostbasedAuthentication=no " + \
        "-o CheckHostIP=no -o ConnectTimeout=10 -o ServerAliveInterval=30 " + \
        "-o BatchMode=yes -o UserKnownHostsFile=/dev/null "

    if ssh_key != None:
      ssh_ops = ssh_ops + "-o IdentityFile=" + ssh_key + " "
    self.base_ssh_cmd = "/usr/bin/ssh " + ssh_ops + username + "@"
    self.base_scp_cmd = "/usr/bin/scp " + ssh_ops

# Runs 32 threads at the same time, this works well because half of the ndoes
# contacted typically are unresponsive and take tcp time out to fail or in
# other cases, they are bandwidth limited while downloading the data for
# install
  def run(self):
    # process each node
    pids = []
    for node in self.nodes:
      pid = os.fork()
      if pid == 0:
        self.task(node)
        os._exit(0)
      pids.append(pid)
      while len(pids) >= 64:
        time.sleep(5)
        to_remove = []
        for pid in pids:
          if os.waitpid(pid, os.P_NOWAIT)[0] != 0:
            to_remove.append(pid)
        for pid in to_remove:
          pids.remove(pid)

    # make sure we cleanly exit
    count = 0
    while True:
      if len(pids) == 0:
        break
      to_remove = []
      for pid in pids:
        if os.waitpid(pid, os.P_NOWAIT)[0] != 0:
          to_remove.append(pid)
      for pid in to_remove:
        pids.remove(pid)
      time.sleep(10)
      if count >= 60:
        for pid in pids:
          os.kill(pid, signal.SIGKILL)
      count += 1
    if self.action == "gather_logs":
      os.system("rm -rf logs.zip")
      os.system("zip -r9 logs.zip logs")
      os.system("rm -rf logs")

  def check_node(self, node):
    self.node_install(node, True)

  def install_node(self, node):
    self.node_install(node, False)

  # node is the hostname that we'll be installing the software stack unto
  # check determines whether or not to check to see if software is already
  #   running and not install if it is.
  def node_install(self, node, check):
    cmd = "%s %s %s@%s:/home/%s/p2pnode.sh &> /dev/null" % (self.base_scp_cmd, \
        self.setup_file, self.username, node, self.username)
    os.system(cmd)

    base_ssh = self.base_ssh_cmd + node + " "
    if check:
        # This prints something if all is good ending this install attempt
      res = ssh_cmd("%s bash /home/%s/p2pnode.sh check %s %s %s" % (base_ssh, \
          self.username, self.install_path, self.namespace, self.md5sum), self.logger)

      if res == "" or res != "Failed!":
        self.logger(node + " no state change...")
        return

    # this helps us leave early in case the node is unaccessible
    cmd = "%s %s %s@%s:/home/%s/%s.tgz &> /dev/null" % (self.base_scp_cmd, \
        self.path_to_files, self.username, node, self.username, self.pool)
    os.system(cmd)
    res = ssh_cmd("%s bash /home/%s/p2pnode.sh setup %s %s" % (base_ssh, \
        self.username, self.install_path, self.pool), self.logger)

    if res == "":
      self.logger(node + " done!")
      if self.update_callback:
        self.update_callback(node, 1)
    else:
      self.logger(node + " failed!" + " " + res)
      if self.update_callback:
        self.update_callback(node, 0)
    return

  def uninstall_node(self, node):
    cmd = "%s %s %s@%s:/home/%s/p2pnode.sh &> /dev/null" % (self.base_scp_cmd, \
        self.setup_file, self.username, node, self.username)
    os.system(cmd)

    base_ssh = self.base_ssh_cmd + node + " "
    res = ssh_cmd("%s bash /home/%s/p2pnode.sh remove %s" % (base_ssh, \
        self.username, self.install_path), self.logger)

    if res == "":
      if self.update_callback:
        self.update_callback(node, 0)
      else:
        self.logger(node + " done!")
    else:
      if self.update_callback:
        self.update_callback(node, 1)
      else:
        self.logger(node + " failed!")

  def gather_logs(self, node):
    os.system("mkdir logs/" + node)
    cmd = "%s %s@%s:%s/node/node.log.* %s/logs/%s/. &> /dev/null" % \
        (self.base_scp_cmd, self.username, node,  self.install_path, \
        self.data_path, node)
    os.system(cmd)
    return

  def cmd(self, node):
    cmd = "%s%s %s 2> /dev/null 1> cmd/%s" % (self.base_ssh_cmd, node, self.args, node)
    os.system(cmd)
    return
    try:
      os.system(cmd)
    except:
      pass
    return
    
# This runs the ssh command monitoring it for any possible failures and raises
# an the KeyboardInterrupt if there is one.
def ssh_cmd(cmd, logger = logger_std_out):
  cmd = cmd + " 0<&-"
  p = subprocess.Popen(cmd.split(' '), stdin=subprocess.PIPE, \
      stdout=subprocess.PIPE, stderr=subprocess.PIPE)
  p.stdin.close()
  os.waitpid(p.pid, 0)
  err = p.stderr.read()
  out = p.stdout.read()
  good_err = re.compile("Warning: Permanently added")
  if good_err.search(err) != None:
    nerr = ''
    for line in err.split('\n'):
      if good_err.search(line) != None:
        continue
      nerr += line + "\n"
    err = nerr
  err = err.strip()
  out = out.strip()

#  logger("err: " + err)
#  logger("out: " + out)
  if err != '':
    return err
  return out

if __name__ == "__main__":
  main()
