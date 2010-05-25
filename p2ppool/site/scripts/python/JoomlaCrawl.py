#!/usr/bin/python
""" This crawls the brunet namespace, find the nodes local tas and its ipop 
    information.  During crawling it also determines if the ring is consistent
    (do a pair of nodes which are neighbors agree that they are neighbors).
    Finally, all data is placed into the database.
"""
import MySQLdb, types, sys, crawl, os, JoomlaDB, time
def main(pool):
  jdb = JoomlaDB.JoomlaDB()
  jc = JoomlaCrawl(pool, jdb)
  jc.run()

class JoomlaCrawl:
  def __init__(self, pool, jdb):
    self.pool = pool

    self.jdb = jdb
    self.log_dir = sys.path[0] + os.sep + ".." + os.sep + ".." + os.sep + "data" \
        + os.sep + pool + os.sep + "crawl.log"

    self.time = time.ctime()

    db = self.jdb.get_db()
    cursor = db.cursor()
    cursor.execute("SELECT rpcport FROM p2ppools WHERE pool = \"" + self.pool + "\"")
    self.port = cursor.fetchone()[0]
    cursor.close()
    db.close()

  def run(self):
    log = open(self.log_dir, "w+")
    log.write("Starting...\n")
    log.close()

    crawler = crawl.Crawler(self.port, self.output)
    crawler.start()
    nodes = crawler.nodes

    fields = ["ip" , "geo_loc", "type", "virtual_ip", "retries", "consistency", \
        "brunet_address", "tcp", "udp", "tunnel", "sas", "neighbor", "cons", "namespace"]

    db = self.jdb.get_db()
    cursor = db.cursor()
    cursor.execute("START TRANSACTION")
    cursor.execute("INSERT INTO " + self.pool + "_count (date) VALUES (\"" + self.time + "\")")
    cursor.execute("SELECT count FROM " + self.pool + "_count WHERE date = \"" + self.time + "\"")
    count = str(cursor.fetchone()[0])

    total_fields = {"tcp" : 0, "udp" : 0, "sas" : 0, "cons" : 0, "consistency" : 0, \
        "retries" : 0, "nodes" : len(nodes), "tunnel" : 0}
    for ba in nodes:
      node = nodes[ba]
      svar = ""
      sval = ""
      for field in fields:
        if field == "ip":
          val = node["ips"].split(",")[0]
        elif field == "brunet_address":
          val = ba.split(':')[2]
        elif field == "neighbor":
          vtmp = node['left'].split(':')
          val = vtmp[2] if len(vtmp) > 2 else ""
        elif field in node:
          val = node[field]
        else:
          val = ""
        svar += field + ", "
        if isinstance(val, types.StringType):
          val = "\"" + val + "\""
        sval += str(val) + ", "

        if field in total_fields:
          total_fields[field] = total_fields[field] + node[field]
      svar += "count"
      sval += count
      query = "INSERT INTO " + self.pool + "_stats (" + svar + ") VALUES (" + sval + ")"
      cursor.execute(query)
    svar = ""
    sval = ""
    total_fields["consistency"] /= len(nodes)
    for field in total_fields:
      svar += field + ", "
      sval += str(total_fields[field]) + ", "
    svar += "count"
    sval += count
    query = "INSERT INTO " + self.pool + "_system_stats (" + svar + ") VALUES (" + sval + ")"
    cursor.execute(query)
    cursor.execute("COMMIT")
    cursor.close()
    db.close()
    self.output("Done...")

  def output(self, msg):
    log = open(self.log_dir, "a")
    log.write(str(msg) + "\n")
    log.close()

if __name__ == "__main__":
  pool = sys.argv[1]
  main(pool)
