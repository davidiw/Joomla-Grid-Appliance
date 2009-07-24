#!/usr/bin/python
"""
Joomla to System interface: Handles the controlling of the local instance as
well as remote instances.
Supported tasks:
  install, check, uninstall, get_logs, prepare, suspend
"""

import MySQLdb, Remoter, sys, types, os, JoomlaPrepare, JoomlaDB, JoomlaBasicNode, JoomlaCrawl, time

def main(task, pool, fork = False):
  if fork:
    if os.fork() == 0:
      app = "python"
      pyapp = sys.path[0] + os.sep + "JoomlaInterface.py"
      args = "%s %s %s %s" % (app, pyapp, task, pool)
      os.execvp(app, args.split(' '))
      pid = os.getpid()
    else:
      return True

  jdb = JoomlaDB.JoomlaDB()
  if not jdb.lock(pool, task, os.getpid()):
    return False

  p2p = JoomlaRemoter(pool, task, jdb)

  try:
    if task == "install" or task == "prepare":
      preparer = JoomlaPrepare.Preparer(pool, p2p.mkbundle)
      preparer.build()

    bn = JoomlaBasicNode.JoomlaBasicNode(pool, jdb)
    if task == "install" or task == "uninstall":
      bn.stop()
    if task != "prepare" and task != "uninstall":
      bn.start()

    if task != "prepare":
      p2p.run()
  except:
    pass

  p2p.cleanup()

class JoomlaRemoter:
  def __init__(self, pool, task, jdb):
    self.task = task
    self.pool = pool
    self.jdb = jdb

    base_path = sys.path[0] + os.sep + ".." + os.sep + ".." + os.sep
    self.ssh_key = base_path + "private" + os.sep + pool + os.sep + "ssh_key"
    self.path_to_files = base_path + "data" + os.sep + pool + os.sep + "install.tgz"
    self.log_dir =  base_path + "data" + os.sep + pool + os.sep + task + ".log"
    os.chdir(base_path + "data")

    db = self.jdb.get_db()
    cursor = db.cursor()
    cursor.execute("SELECT user_name, install_path, mkbundle FROM p2ppools WHERE pool = \"" + self.pool + "\"")
    res = cursor.fetchone()
    self.ssh_username = res[0]
    self.install_path = res[1]
    self.mkbundle = str(res[2]) == "1"
    cursor.close()
    db.close()

  def output(self, msg):
    log = open(self.log_dir, "a")
    log.write(str(msg) + "\n")
    log.close()

  def get_nodes(self, all, ref = "name"):
    db = self.jdb.get_db()
    cursor = db.cursor()
    query = "SELECT " + ref + " FROM " + self.pool + "_pool"
    if all:
      cursor.execute(query)
    else:
      cursor.execute(query + " WHERE installed = 1")
    dbnodes = cursor.fetchall()

    nodes = []
    for node in dbnodes:
      nodes.append(node[0])
    return nodes

  def install_callback(self, node, res):
    db = self.jdb.get_db()
    cursor = db.cursor()
    query = "UPDATE " + self.pool + "_pool SET installed = " + str(res) + \
        " WHERE ip = \"" + node + "\" OR name = \"" + node + "\""
    cursor.execute(query)
    cursor.close()
    db.close()

  def run(self):
    if self.task == "crawl":
      crawl = JoomlaCrawl.JoomlaCrawl(self.pool, self.jdb)
      crawl.run()
    else:
      nodes = self.get_nodes(True)
      plab = Remoter.Remoter(self.task, nodes, username = self.ssh_username, \
        path_to_files = self.path_to_files, update_callback = self.install_callback, \
        ssh_key = self.ssh_key, install_path = self.install_path, pool = self.pool, \
        logger = self.output)
      plab.run()

  def cleanup(self):
    self.jdb.unlock(self.pool, self.task)

if __name__ == "__main__":
  task = sys.argv[1]
  pool = sys.argv[2]
  fork = False
  if len(sys.argv) > 3:
    fork = sys.argv[3]

  main(task, pool, fork)
