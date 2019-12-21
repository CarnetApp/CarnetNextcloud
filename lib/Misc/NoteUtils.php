<?php
namespace OCA\Carnet\Misc;
class NoteUtils{
    public static $defaultCarnetNotePath = "Documents/QuickNote";
    public static function getShortTextFromHTML($html){
        return mb_substr(trim(preg_replace('#<[^>]+>#', ' ', $html)),0, 150);
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
                
                $meta['shorttext'] = self::getShortTextFromHTML($node->get('index.html')->getContent());
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
                
                $meta['shorttext'] = self::getShortTextFromHTML($zipFile->getEntryContents("index.html"));
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