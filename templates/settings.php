<?php

$currentpath = __DIR__."/CarnetElectron/";
$root = \OCP\Util::linkToAbsolute("carnet","templates");
$root = parse_url($root, PHP_URL_PATH); 
$file = file_get_contents($currentpath."settings.html");

//
$file = str_replace("href=\"","href=\"".$root."/CarnetElectron/",$file);

preg_match_all('/<script.*?src=\"(.*?\.js(?:\?.*?)?)"/si', $file, $matches, PREG_PATTERN_ORDER);
for ($i = 0; $i < count($matches[1]); $i++) {
    if($matches[1][$i] === "libs/jquery.min.js")
        continue;
    script("carnet","../templates/CarnetElectron/".substr($matches[1][$i],0,-3));
}
if($_['carnet_display_fullscreen']==="yes")
    script("carnet","../templates/CarnetElectron/compatibility/nextcloud/browser_fullscreen");
$file = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $file);
$file = str_replace("src=\"","defer src=\"".$root."/CarnetElectron/",$file);
echo $file;
echo "<span style=\"display:none;\" id=\"root-url\">".$root."/CarnetElectron/</span>";
?>
