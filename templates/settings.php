<?php
global $fullscreen;
$fullscreen = $_['carnet_display_fullscreen'];
$currentpath = __DIR__."/CarnetElectron/";
$root = \OCP\Util::linkToAbsolute("carnet","templates");
$root = parse_url($root, PHP_URL_PATH); 
$file = file_get_contents($currentpath."settings.html");
$file = str_replace("href=\"","href=\"".$root."/CarnetElectron/",$file);
$file = preg_replace_callback('/<script(.*?)src=\"(.*?\.js(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    if($matches[2] === "libs/jquery.min.js" AND $fullscreen === "no")
        return "<script src=\"\"";
    return "<script".$matches[1]."src=\"".$matches[2]."?mtime=".filemtime($currentpath.$matches[2])."\"";
}, $file);
// token is needed to pass the csfr check
$file .= "<span style=\"display:none;\" id=\"token\">".$_['requesttoken']."</span>";
if($_['carnet_display_fullscreen']==="yes"){
    $file .= "<script src=\"compatibility/nextcloud/fullscreen.js?mtime=\"></script>";
    if($_['nc_version']>=16)
        style("carnet","../templates/CarnetElectron/compatibility/nextcloud/nc16");
}
else if($_['nc_version']>=14)
    style("carnet","../templates/CarnetElectron/compatibility/nextcloud/nc14-header");
$nonce = "";
if (method_exists(\OC::$server, "getContentSecurityPolicyNonceManager")){
    $nonce = \OC::$server->getContentSecurityPolicyNonceManager()->getNonce();
}
$file = str_replace("src=\"","defer nonce='".$nonce."' src=\"".$root."/CarnetElectron/",$file);
echo $file;
echo "<span style=\"display:none;\" id=\"root-url\">".$root."/CarnetElectron/</span>";
echo "<span style=\"display:none;\" id=\"logout-token\">".urlencode(\OCP\Util::callRegister())."</span>";
?>
