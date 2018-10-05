<?php
$currentpath = getcwd()."/CarnetElectron/";

$file = file_get_contents($currentpath."reader/reader.html");
$file = str_replace("<!ROOTPATH>", "CarnetElectron/", $file);

$file = preg_replace_callback('/<script(.*?)src=\"(.*?\.js(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    return "<script".$matches[1]."src=\"".$matches[2]."?mtime=".filemtime($currentpath.$matches[2])."\"";
}, $file);
$root = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));

$file = str_replace("<!ROOTURL>", "CarnetElectron/", $file);
$file = str_replace("<!APIURL>", "../../../index.php/apps/carnet/", $file);


echo $file;

?>