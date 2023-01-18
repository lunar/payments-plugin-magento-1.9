<?php

ini_set("display_errors", "1");
error_reporting(E_ALL);

define("VAR_DIR", BP . DIRECTORY_SEPARATOR . "var");
define("LOGS_DIR_NAME", VAR_DIR . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR . "lunar");
define("LOGS_DATE_FORMAT", "Y-m-d-h-i-s");


class Lunar_Payment_LogController extends Mage_Core_Controller_Front_Action {
  public function deleteLogsAction() {
    $files = glob(LOGS_DIR_NAME . DIRECTORY_SEPARATOR . "*.log");
    foreach($files as $file) {
      unlink($file);
    }
  }

  public function hasLogsAction() {
    $files = glob(LOGS_DIR_NAME . DIRECTORY_SEPARATOR . "*.log");
    echo count($files) > 0;
  }

  public function exportAction() {
    $filename = LOGS_DIR_NAME . DIRECTORY_SEPARATOR . "export.zip";
    $zip = new \ZipArchive();
    $zip->open($filename, \ZipArchive::CREATE);

    $files = glob(LOGS_DIR_NAME . DIRECTORY_SEPARATOR . "*.log");
    foreach($files as $file) {
      $zip -> addFile($file, basename($file));
    }

    $zip -> close();

    $content = base64_encode(file_get_contents($filename));
    unlink($filename);

    echo $content;
  }

  public function writtableAction() {
    $response = [
      "dir" => VAR_DIR,
      "writable" => is_writable(VAR_DIR),
    ];

    echo json_encode($response);
  }

  public function logAction() {
    $post = json_decode($this->getRequest()->getParam("data"), true);

    if (!is_dir(LOGS_DIR_NAME)) {
      mkdir(LOGS_DIR_NAME, 0777, true);
    }

    $date = date(LOGS_DATE_FORMAT, ($post["date"] / 1000));
    $id = $post["context"]['custom']["quoteId"];
    $filename = LOGS_DIR_NAME . DIRECTORY_SEPARATOR . $date . "___" . $id . ".log";

    if (!file_exists($filename)) {
      $separator = "============================================================";
      file_put_contents($filename, $separator . PHP_EOL . json_encode($post) . PHP_EOL . $separator . PHP_EOL . PHP_EOL);
    }

    $newContent = PHP_EOL . date(LOGS_DATE_FORMAT) . ": " . $post["message"];
    file_put_contents($filename, $newContent, FILE_APPEND);
  }
}
?>
