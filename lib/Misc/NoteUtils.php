<?php
namespace OCA\Carnet\Misc;

class NoteUtils{

    public function getMetadata($carnetFolder, $path){
        $meta = array();
        $tmppath = tempnam(sys_get_temp_dir(), uniqid().".zip");
        $node = $carnetFolder->get($path);
        
        file_put_contents($tmppath, $node->fopen("r"));
            $zipFile = new \PhpZip\ZipFile();
            $zipFile->openFromStream(fopen($tmppath, "r")); //issue with encryption when open directly + unexpectedly faster to copy before Oo'
            $meta['lastmodfile'] = $node->getMTime();
            try{
                $meta['metadata'] = json_decode($zipFile->getEntryContents("metadata.json"));
            } catch(\PhpZip\Exception\ZipNotFoundEntry $e){
            
            }
            try{
            
            $meta['shorttext'] = mb_substr(trim(preg_replace('#<[^>]+>#', ' ', $zipFile->getEntryContents("index.html"))),0, 150);
            $i=0;
            try{
                foreach($zipFile->getListFiles() as $f){
                    if(substr($f, 0, strlen("data/preview")) === "data/preview"){

                        $meta['previews'][$i] = "./note/getmedia?note=".$path."&media=".$f;
                        $i++;
                        if($i>2)
                            break;
                    }
                    
                }
            }
            catch(\PhpZip\Exception\ZipNotFoundEntry $e){
                
            }
        } catch(\PhpZip\Exception\ZipNotFoundEntry $e){
            $meta['shorttext'] = "";
            
        }
        unlink($tmppath);
        return $meta;
        
    }
}
?>