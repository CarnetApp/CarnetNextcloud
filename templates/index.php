<?php

$currentpath = substr(get_defined_vars()["file"],strlen(getcwd())+1, -strlen("index.php"))."/CarnetElectron/";

$file = file_get_contents($currentpath."index.html");
//
$file = str_replace("href=\"","href=\"".substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']))."/CarnetElectron/",$file);

preg_match_all('/<script.*?src=\"(.*?\.js(?:\?.*?)?)"/si', $file, $matches, PREG_PATTERN_ORDER);
for ($i = 0; $i < count($matches[1]); $i++) {
    script("carnet","../templates/CarnetElectron/".substr($matches[1][$i],0,-3));
}
if($_['carnet_display_fullscreen']==="yes")
    script("carnet","../templates/CarnetElectron/compatibility/nextcloud/browser_fullscreen");
$file = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $file);
$root = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
$file = str_replace("src=\"","defer src=\"".$root."/CarnetElectron/",$file);
echo $file;
echo "<span style=\"display:none;\" id=\"root-url\">".$root."/CarnetElectron/</span>";
?>