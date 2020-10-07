<?php
global $currentpath;
global $root;
global $appVersion;
$appVersion = $_['app_version'];
$currentpath = __DIR__."/CarnetElectron/";
$root = \OCP\Util::linkToAbsolute("carnet","templates");
$root = parse_url($root, PHP_URL_PATH); 

$file = file_get_contents($currentpath."exporter/exporter.html");
$file = preg_replace_callback('/<link(.*?)href=\"(.*?\.css(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    global $appVersion;
    global $root;
    return "<link".$matches[1]."href=\"".$matches[2]."?v=".$appVersion."\"";
}, $file);
$file = str_replace("href=\"","href=\"".$root."/CarnetElectron/exporter/",$file);

$file = preg_replace_callback('/<script(.*?)src=\"(.*?\.js(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    global $fullscreen;
    global $appVersion;
    global $root;

    return "<script".$matches[1]."src=\"".$root."/CarnetElectron/exporter/".$matches[2]."?v=".$appVersion."\"";
}, $file);
// token is needed to pass the csfr check
$file .= "<span style=\"display:none;\" id=\"token\">".$_['requesttoken']."</span>";


$file .= "<script src=\"".$root."/CarnetElectron/compatibility/nextcloud/fullscreen.js?v=".$appVersion."\"></script>";

$file = str_replace("<!ROOTPATH>", $root."/CarnetElectron/", $file);
$root = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
$urlGenerator = \OC::$server->getURLGenerator();
$file = str_replace("<!ROOTURL>", $root."/CarnetElectron/", $file);
if (method_exists(\OC::$server, "getContentSecurityPolicyNonceManager")){
    $nonce = \OC::$server->getContentSecurityPolicyNonceManager()->getNonce();
    $file = str_replace("src=\"","defer nonce='".$nonce."' src=\"",$file);
}
$file = str_replace("<!APIURL>", parse_url($urlGenerator->linkToRouteAbsolute("carnet.page.index"), PHP_URL_PATH), $file);
echo $file;

?>