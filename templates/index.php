<?php
global $currentpath;
global $root;
global $fullscreen;
global $appVersion;
$fullscreen = $_['carnet_display_fullscreen'];
$appVersion = $_['app_version'];
$currentpath = __DIR__."/CarnetElectron/";
$root = \OCP\Util::linkToAbsolute("carnet","templates");
$file = file_get_contents($currentpath."index.html");
$root = parse_url($root, PHP_URL_PATH); 

$file = preg_replace_callback('/<link(.*?)href=\"(.*?\.css(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    global $appVersion;
    return "<link".$matches[1]."href=\"".$matches[2]."?v=".$appVersion."\"";
}, $file);
$file = str_replace("href=\"","href=\"".$root."/CarnetElectron/",$file);

$file = preg_replace_callback('/<script(.*?)src=\"(.*?\.js(?:\?.*?)?)"/s',function ($matches) {
    global $currentpath;
    global $fullscreen;
    global $appVersion;

    if($matches[2] === "libs/jquery.min.js" AND $fullscreen === "no")
        return "<script ";
    return "<script".$matches[1]."src=\"".$matches[2]."?v=".$appVersion."\"";
}, $file);
// token is needed to pass the csfr check
$file .= "<span style=\"display:none;\" id=\"token\">".$_['requesttoken']."</span>";
if($_['carnet_display_fullscreen']==="yes"){
    
    $file = str_replace('</head>', "
    <link rel=\"apple-touch-icon-precomposed\" href=\"".image_path('', 'favicon-touch.png')."\" />
    <link rel=\"icon\" href=\"".image_path('', 'favicon.ico')."\">
    <link rel=\"mask-icon\" sizes=\"any\" href=\"".image_path('', 'favicon-mask.svg')."\" color=\"".$theme->getColorPrimary()."\">
    <link rel=\"manifest\" href=\"".image_path('', 'manifest.json')."\">
    <script src=\"compatibility/nextcloud/fullscreen.js?v=".$appVersion."\"></script>
    </head>", $file);
    if($_['nc_version']>=16)
        style("carnet","../templates/CarnetElectron/compatibility/nextcloud/nc16");
    
}
else {
    if($_['nc_version']>=14)
    style("carnet","../templates/CarnetElectron/compatibility/nextcloud/nc14-header");
}
$nonce = "";
if (method_exists(\OC::$server, "getContentSecurityPolicyNonceManager")){
    $nonce = \OC::$server->getContentSecurityPolicyNonceManager()->getNonce();
}
else{
    style("carnet","../templates/CarnetElectron/compatibility/nextcloud/owncloud");
}

$file = str_replace("src=\"","defer nonce='".$nonce."' src=\"".$root."/CarnetElectron/",$file);
echo $file;
echo "<span style=\"display:none;\" id=\"root-url\">".$root."/CarnetElectron/</span>";
?>