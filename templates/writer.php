<?php
$currentpath = __DIR__."/CarnetElectron/";
$root = \OCP\Util::linkToAbsolute("carnet","templates");

$file = file_get_contents($currentpath."reader/reader.html");
$file = str_replace("<!ROOTPATH>", $root."/CarnetElectron/", $file);

$file = preg_replace_callback('/<script(.*?)src=\"(.*?\.js(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    return "<script".$matches[1]."src=\"".$matches[2]."?mtime=".filemtime($currentpath.$matches[2])."\"";
}, $file);
$root = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
$urlGenerator = \OC::$server->getURLGenerator();
$file = str_replace("<!ROOTURL>", $root."/CarnetElectron/", $file);
$file = str_replace("<!APIURL>", $urlGenerator->linkToRouteAbsolute("carnet.page.index"), $file);
echo $file;

?>