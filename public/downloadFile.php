<?php
include_once "../src/config.php";

$file_url = $_GET['n'];

header('Content-Type: application/csv');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . basename($file_url.".txt") . "\"");
header('Pragma: no-cache');

readfile(PATH_CONTAB.$file_url.".txt");

?>