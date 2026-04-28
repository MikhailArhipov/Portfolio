<?
include('pricescore.php');
$exit = true;
foreach ($checkFile as $el)
    if (strpos($_GET['file'], $el) !== false) $exit = false;
if ($exit) die;
header('Content-type: plain/text');
header('Content-Disposition: attachment; filename=' . $_GET['file']);
readfile(dirname(__FILE__) . '/' . $_GET['file']);
