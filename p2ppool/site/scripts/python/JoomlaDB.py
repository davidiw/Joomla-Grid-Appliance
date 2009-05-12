#!/usr/bin/python
import MySQLdb, os, re, sys

class JoomlaDB:
  def __init__(self):
    config_path = sys.path[0] + os.sep + ".." + os.sep + ".." + os.sep + ".." \
        + os.sep + ".." + os.sep + "configuration.php"
    config_file = open(config_path, "r")
    config = config_file.read()
    config_file.close()

    self.password = self.find_val(config, "password")
    self.username = self.find_val(config, "user")
    self.database = self.find_val(config, "db")
    self.host = self.find_val(config, "host")

  def find_val(self, config, value):
    line_re =  re.compile("\$" + value + "\s*=[^;]*")
    value_re = re.compile("['][^']*[']")
    no_quotes = re.compile("[^']+")

    val = line_re.search(config)
    if val != None:
      val = value_re.search(val.group())
      if val != None:
        val = no_quotes.search(val.group())
        if val != None:
          val = val.group()
    if val == None:
      val = ""

    return val



  def get_db(self):
    return MySQLdb.connect(host = self.host, user = self.username, \
        passwd = self.password, db = self.database)

  def get_pid(self, pool, task):
    db = self.get_db()
    cursor = db.cursor()

    cursor.execute("SELECT pid FROM p2ppool_taskman WHERE pool = \"%s\" and task = \"%s\"" \
        % (pool, task))
    pid = cursor.fetchone()

    try:
      pid = pid[0]
    except:
      pass

    cursor.close()
    db.close()

    return pid

  def check(self, pool, task):
    pid = self.get_pid(pool, task)
    return self.check_pid(pid)

  def check_pid(self, pid):
    return os.path.exists("/proc/" + str(pid))

  def lock(self, pool, task, pid):
    pid = str(pid)
    db = self.get_db()
    cursor = db.cursor()
    cursor.execute("LOCK TABLES p2ppool_taskman WRITE")
    cursor.execute("SELECT pid FROM p2ppool_taskman WHERE pool = \"%s\" and task = \"%s\"" \
        % (pool, task))

    results = cursor.fetchall()
    for res in results:
      if len(res) < 1:
        continue
      opid = res[0]

      if self.check_pid(opid):
        cursor.execute("UNLOCK TABLES")
        cursor.close()
        db.close()
        return False
      else:
        cursor.execute("DELETE FROM p2ppool_taskman WHERE pool = \"%s\" and pid = %s and task = \"%s\"" \
            % (pool, str(opid), task))

    cursor.execute("INSERT INTO p2ppool_taskman (pool, task, pid) VALUES (\"%s\", \"%s\", %s)" \
        % (pool, task, str(pid)))
    cursor.execute("UNLOCK TABLES")
    cursor.close()
    db.close()
    return True

  def unlock(self, pool, task):
    db = self.get_db()
    cursor = db.cursor()
    cursor.execute("DELETE FROM p2ppool_taskman WHERE pool = \"%s\" and task = \"%s\"" \
        % (pool, task))
    cursor.close()
    db.close()
