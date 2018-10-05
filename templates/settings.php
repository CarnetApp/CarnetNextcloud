<?php

$currentpath = substr(get_defined_vars()["file"],0, -strlen("settings.php"))."/CarnetElectron/";
$root = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
if (strpos(__DIR__, '/snap/') !== false) {
   $root = "/extra-apps/carnet/templates";
}
$file = file_get_contents($currentpath."settings/index.html");

//
$file = str_replace("href=\"","href=\"".$root."/CarnetElectron/",$file);

preg_match_all('/<script.*?src=\"(.*?\.js(?:\?.*?)?)"/si', $file, $matches, PREG_PATTERN_ORDER);
for ($i = 0; $i < count($matches[1]); $i++) {
    script("carnet","../templates/CarnetElectron/".substr($matches[1][$i],0,-3));
}
if($_['carnet_display_fullscreen']==="yes")
    script("carnet","../templates/CarnetElectron/compatibility/nextcloud/browser_fullscreen");
$file = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $file);
$file = str_replace("src=\"","defer src=\"".$root."/CarnetElectron/",$file);
echo $file;
echo "<span style=\"display:none;\" id=\"root-url\">".$root."/CarnetElectron/</span>";
?>
