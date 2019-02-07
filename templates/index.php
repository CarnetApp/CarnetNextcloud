<?php
global $currentpath;
global $root;
$currentpath = __DIR__."/CarnetElectron/";
$root = \OCP\Util::linkToAbsolute("carnet","templates");
$file = file_get_contents($currentpath."index.html");
$root = parse_url($root, PHP_URL_PATH); 

$file = preg_replace_callback('/<link(.*?)href=\"(.*?\.css(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    return "<link".$matches[1]."href=\"".$matches[2]."?mtime=".filemtime($currentpath.$matches[2])."\"";
}, $file);
$file = str_replace("href=\"","href=\"".$root."/CarnetElectron/",$file);

$file = preg_replace_callback('/<script(.*?)src=\"(.*?\.js(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    if($matches[2] === "libs/jquery.min.js")
        return "";
    return "<script".$matches[1]."src=\"".$matches[2]."?mtime=".filemtime($currentpath.$matches[2])."\"";
}, $file);

if($_['carnet_display_fullscreen']==="yes")
    script("carnet","../templates/CarnetElectron/compatibility/nextcloud/browser_fullscreen");
else {
    if($_['nc_version']>=14)
    style("carnet","../templates/CarnetElectron/compatibility/nextcloud/nc14-header");
}
$file = str_replace("src=\"","defer nonce='".\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()."' src=\"".$root."/CarnetElectron/",$file);
echo $file;
echo "<span style=\"display:none;\" id=\"root-url\">".$root."/CarnetElectron/</span>";
?>