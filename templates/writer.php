<?php
$currentpath = getcwd()."/CarnetElectron/";

$file = file_get_contents($currentpath."reader/reader.html");
//
$file = str_replace("href=\"","href=\"".substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']))."/CarnetElectron/",$file);
$file = str_replace("<!ROOTPATH>", "", $file);
//if($_['carnet_display_fullscreen']=="yes")
//    script("carnet","../templates/CarnetElectron/compatibility/nextcloud/reader_fullscreen");
//filemtime (

    $file = preg_replace_callback('/<script(.*?)src=\"(.*?\.js(?:\?.*?)?)"/s',function ($matches) {
        global $currentpath;
        return "<script".$matches[1]."src=\"".$matches[2]."?mtime=".filemtime($currentpath.$matches[2])."\"";
    }, $file);
$root = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
$file = str_replace("src=\"","defer src=\"".$root."/CarnetElectron/",$file);




echo $file;
echo "<span style=\"display:none;\" id=\"root-url\">".$root."/CarnetElectron/</span>";
echo "<span style=\"display:none;\" id=\"api-url\">../../../index.php/apps/carnet/</span>";

?>