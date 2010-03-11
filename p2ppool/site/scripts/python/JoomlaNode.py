#!/usr/bin/python
import MySQLdb, os, sys, JoomlaDB, shutil, subprocess, signal, time
"""
This starts, stops, and checks the locally running Node instance.
"""

def main(pool):
  jdb = JoomlaDB.JoomlaDB()
  bn = JoomlaNode(pool, jdb)
  bn.start()

class JoomlaNode:
  def __init__(self, pool, jdb):
    self.pool = pool
    self.jdb = jdb
    self.base_path = sys.path[0] + os.sep + ".." + os.sep + ".." + os.sep + "data" + os.sep +  pool + os.sep
    self.node_path = self.base_path + "node" + os.sep
    self.input = self.base_path + "files.zip"
    self.output = self.base_path + "node"
    self.child = False
    self.task = "basicnode"

  def start(self):
    if self.jdb.check(self.pool, self.task):
      return

    pid = os.fork()
    if pid != 0:
      return

    if not self.jdb.lock(self.pool, self.task, os.getpid()):
      return

    self.child = True
    os.system("rm -rf " + self.node_path)
    os.system("mkdir -p " + self.node_path)
    os.system("unzip -o -d " + self.node_path + " " + self.input + " &> /dev/null")
    shutil.copy(self.base_path + "node.config", self.node_path + "node.config")
    os.chdir(self.node_path)
    app = "P2PNode.exe -n "

    args = "/usr/bin/mono " + self.node_path + app + self.node_path + \
        "node.config &> " + self.node_path + "out &"
    os.execvp("/usr/bin/mono", args.split(' '))


  def stop(self):
    kill = False
    pid = self.jdb.get_pid(self.pool, self.task)
    while self.jdb.check(self.pool, self.task):
      if not kill:
        os.kill(pid, signal.SIGINT)
      else:
        os.kill(pid, signal.SIGKILL)
      count = 0
      while self.jdb.check(self.pool, self.task) and count < 5:
        time.sleep(1)
        count += 1
        if self.child:
          os.waitpid(pid, os.P_NOWAIT)
    self.child = False
    self.jdb.unlock(self.pool, self.task)

if __name__ == "__main__":
  pool = sys.argv[1]
  main(pool)
