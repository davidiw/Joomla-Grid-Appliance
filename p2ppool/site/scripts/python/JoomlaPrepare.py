#!/usr/bin/python
import sys, os, shutil

def main():
  pool = sys.argv[1]
  mkbundle = sys.argv[2]
  preparer = Preparer(pool, mkbundle)
  preparer.build()

class Preparer:
  def __init__(self, pool, mkbundle):
    self.base_path = sys.path[0] + os.sep + ".." + os.sep + ".." + os.sep + "data" + os.sep
    if mkbundle:
      self.node_basepath = self.base_path + "mkbundlenode" + os.sep
    else:
      self.node_basepath = self.base_path + "mononode" + os.sep

    self.pool = pool
    self.base_path +=  pool + os.sep
    self.input = self.base_path + "files.zip"
    self.output = self.base_path + "install.tgz"
    self.tmp = self.base_path + "tmp" + os.sep
    self.inpath = self.tmp + "binary" + os.sep
    self.outpath = self.tmp + "node" + os.sep
    self.mkbundle = mkbundle
    
  def build(self):
    """  Extracts files, optionally mkbundles, adds config, and then creates install.tgz. """
    os.system("mkdir -p " + self.inpath)
    os.system("mkdir " + self.outpath)
    os.chdir(self.tmp)
    os.system("unzip -o -d " + self.inpath + " " + self.input + " &> /dev/null")
    if(self.mkbundle):
      self.prepare_mkbundle()
    else:
      self.prepare()
    os.system("cp -axf " + self.node_basepath + "* " + self.outpath + os.sep + ".")
    os.chdir(self.tmp)
    shutil.copy(self.base_path + self.pool + ".config", self.outpath + self.pool + ".config")
    os.system("echo " + self.pool + " > " + self.outpath + "pool")
    os.system("tar -czf " + self.output + " node")
    shutil.rmtree(self.tmp)

  def prepare(self):
    os.system("cp " + self.inpath + "* " + self.output + os.sep + ".")

  def prepare_mkbundle(self):
    os.chdir(self.inpath)
    dlls = "Mono.Posix.dll "
    for i in os.walk('.'):
      files = i[2]
      for file in files:
        if file.endswith(".dll"):
          dlls += file + " "
    os.system("mkbundle2 -o basicnode --deps --config-dir . --static -z BasicNode.exe " + dlls)
    shutil.copy(self.inpath + "basicnode", self.outpath + "basicnode")

if __name__ == '__main__':
  main()
