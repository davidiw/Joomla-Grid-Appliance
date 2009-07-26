<?php
class Utils {
  // Specifies a file on the host to transfer to the client.
  static function transferFile($file, $filename) {
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Length: '.filesize($file));
    header('Content-Disposition: attachment; filename='.$filename);
    ob_end_flush();
    Utils::readfileChunked($file);
  }

  static function readfileChunked($filename,$retbytes=true) {
     $chunksize = 1*(1024*1024); // how many bytes per chunk
     $buffer = '';
     $cnt = 0;
     $handle = fopen($filename, 'rb');
     if ($handle === false) {
       return false;
     }
     while (!feof($handle)) {
       $buffer = fread($handle, $chunksize);
       echo $buffer;
       ob_flush();
       flush();
       if ($retbytes) {
         $cnt += strlen($buffer);
       }
     }
     $status = fclose($handle);
     if ($retbytes && $status) {
       return $cnt; // return num. bytes delivered like readfile() does.
     }
     return $status;
  } 
}
?>
