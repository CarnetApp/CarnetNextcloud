<?php
$currentpath = substr(get_defined_vars()["file"],strlen(getcwd())+1, -strlen("writer.php"))."/CarnetElectron/";

$file = file_get_contents($currentpath."reader/reader.html");
//
$file = str_replace("href=\"","href=\"".substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']))."/CarnetElectron/",$file);
$file = str_replace("<!ROOTPATH>", "", $file);
preg_match_all('/<script.*?src=\"(.*?\.js(?:\?.*?)?)"/si', $file, $matches, PREG_PATTERN_ORDER);
for ($i = 0; $i < count($matches[1]); $i++) {

    $url = substr($matches[1][$i],0,-3);
    if (strpos($url, 'jquery.min') == false) //jquery already in nextcloud
        script("carnet","../templates/CarnetElectron/".$url);
}
if($_['carnet_display_fullscreen']=="yes")
    script("carnet","../templates/CarnetElectron/compatibility/nextcloud/reader_fullscreen");
$file = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $file);
$root = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
$file = str_replace("src=\"","defer src=\"".$root."/CarnetElectron/",$file);
echo $file;
echo "<span style=\"display:none;\" id=\"root-url\">".$root."/CarnetElectron/</span>";
?>