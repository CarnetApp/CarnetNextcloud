<?php
namespace OCA\Carnet\Misc;
class NoteUtils{
    public static $defaultCarnetNotePath = "Documents/QuickNote";

    public static function endsWith($string, $endString) 
    { 
        $len = strlen($endString); 
        if ($len == 0) { 
            return true; 
        } 
        return (substr($string, -$len) === $endString); 
    }
    public static function getTextFromHTML($html){ //not optimized at alllll....
        return 
            //remove first and last br
            preg_replace('/^(<br\s*\/?>)*|(<br\s*\/?>)*$/i', '',
            //multiple to single br
            preg_replace('/(<br[^>]*>\s*){2,}/', '<br/>',
            //white spaces
            trim(
            //putting back [linebr] to br
            str_replace('[linebr]', '<br>',
            //removing html tags
            preg_replace('#<[^>]+>#', ' ', 
            //replacing all br by linebr
            preg_replace('/(<br\ ?\/?>)/', '[linebr]', 
            //all div to [linebr]
            preg_replace("/<div>(.*?)<\/div>/", "[linebr]$1",$html)
        ))))));
    }
    public static function getShortText($text){ //not optimized at alllll....
        return mb_substr($text, 0, 150);
    }

    public static function isFolderNote($path, $carnetFolder){
        $node = $carnetFolder->get($path);
        return $node->getType() === "dir" && self::isNote($path);
    }

    public static function isNote($path){
        return self::endsWith($path, ".sqd");
    }

    public static function removeAccents($str) {
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
        return str_replace($a, $b, $str);
    }

    public function getMetadata($carnetFolder, $path){
        $meta = array();
        $node = $carnetFolder->get($path);
        if($node->getType() === "dir"){
            $meta['lastmodfile'] = $node->getMTime();
            try{
                $meta['metadata'] = json_decode($node->get('metadata.json')->getContent());
            } catch(\OCP\Files\NotFoundException $e){
            
            }
            try{
                if($node->nodeExists('index.html')) {
                    $text = self::getTextFromHTML($node->get('index.html')->getContent());
                    $meta['isMarkdown'] = false;
                }
                else {
                    $text = $node->get('note.md')->getContent();
                    $meta['isMarkdown'] = true;
                }
                $meta['shorttext'] = self::getShortText($text);
                $meta['text'] = $text;
                $meta['media'] = array();
                $meta['previews'] = array();

                try{
                    foreach($node->get('data')->getDirectoryListing() as $in){
                        if(substr($in->getName(), 0, strlen("preview")) === "preview"){
                            array_push($meta['previews'],"./note/getmedia?note=".$path."&media=data/".$in->getName());
                        } else  {
                            array_push($meta['media'], "./note/getmedia?note=".$path."&media=data/".$in->getName());
                        }
                        
                    }
                }
                catch(\OCP\Files\NotFoundException$e){
                    
                }
            }
            catch(\OCP\Files\NotFoundException $e){
                    
            }
        }
        else{
            $tmppath = tempnam(sys_get_temp_dir(), uniqid().".zip");
            
            file_put_contents($tmppath, $node->fopen("r"));
                $zipFile = new \PhpZip\ZipFile();
                $zipFile->openFromStream(fopen($tmppath, "r")); //issue with encryption when open directly + unexpectedly faster to copy before Oo'
                $meta['lastmodfile'] = $node->getMTime();
                try{
                    $meta['metadata'] = json_decode($zipFile->getEntryContents("metadata.json"));
                } catch(\PhpZip\Exception\ZipNotFoundEntry $e){
                
                }
                try{
                $text = self::getTextFromHTML($zipFile->getEntryContents("index.html"));
                $meta['shorttext'] = self::getShortText($text);
                $meta['text'] = strtolower(self::removeAccents($text));
                $meta['media'] = array();
                $meta['previews'] = array();

                try{
                    foreach($zipFile->getListFiles() as $f){
                        if(substr($f, 0, strlen("data/preview")) === "data/preview"){
                            array_push($meta['previews'],"./note/getmedia?note=".$path."&media=".$f);
                        } else if(substr($f, 0, strlen("data/")) === "data/") {
                            array_push($meta['media'],"./note/getmedia?note=".$path."&media=".$f);
                        }
                        
                    }
                }
                catch(\PhpZip\Exception\ZipNotFoundEntry $e){
                    
                }
            } catch(\PhpZip\Exception\ZipNotFoundEntry $e){
                $meta['shorttext'] = "";
                
            }
            unlink($tmppath);
        }
        return $meta;
        
    }
}
?>