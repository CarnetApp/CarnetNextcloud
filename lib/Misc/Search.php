<?php

namespace OCA\Carnet\Misc;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCA\Carnet\Misc\CacheManager;
use OCP\IDBConnection;

class Search {
    private $output;
    private $appName;
    private $CarnetFolder;
    private $Config;
    private $rootFolder;
    private $searchCache;
    private $current=0;
    private $from;
    private $pathArray = array ();

    /**
         * @param string $appName
         * @param IRootFolder $rootFolder
    */
public function __construct($AppName, $RootFolder,  $Config, IDBConnection $IDBConnection, $userId){
    $this->appName = $AppName;
    $this->Config = $Config;
    $this->db = $IDBConnection;
    $this->rootFolder = $RootFolder;
    $this->userId = $userId;
    $folder = $this->Config->getUserValue($this->userId , $this->appName, "note_folder");
    
    if(empty($folder))
        $folder= 'Documents/QuickNote';
    try {
        $this->CarnetFolder = $this->rootFolder->getUserFolder($this->userId)->get($folder);
    } catch(\OCP\Files\NotFoundException $e) {
        $this->CarnetFolder = $this->rootFolder->getUserFolder($this->userId)->newFolder($folder);
    }
}

protected function removeAccents($str) {
    $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
    $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
    return str_replace($a, $b, $str);
}


public function startSearch($query, $from) {
    $query = $this->removeAccents($query);
    $query = strtolower($query);
    $this->data = array();
    $this->startTime = time();
    $this->from=$from;
    $searchInPath = true;
    if($from==0){
        $this->searchInCache($query);
        if(sizeof($this->data)>0)
            $searchInPath = false;
    }    
    $this->current = 1;    
    $result = array();
    $result['end'] = false;
    if($searchInPath)
        $result['end'] = $this->search("", $this->CarnetFolder, $query,0);
    $result['next'] = $this->current;
    $result['files'] = $this->data;
    return $result;
}

private function searchInCache($query){
    $cache = new CacheManager($this->db, $this->CarnetFolder);
    $metadataFromCache = $cache->search($query);
    foreach($metadataFromCache as $path => $mTime){    
        $file = array();
        $file['name'] = "none";
        $file['path'] = $path;
        $file['isDir'] = false;
        $file['mtime'] = $mTime;
        
        array_push($this->data, $file);
        array_push($this->pathArray, $path);
        
    }
}

private function getCacheFolder(){
    try {
        return $this->rootFolder->getUserFolder($this->userId)->get(".carnet/cache/".$this->userId);
    } catch(\OCP\Files\NotFoundException $e) {
        $folder = $this->rootFolder->getUserFolder($this->userId)->newFolder(".carnet/cache/".$this->userId, 0777, true);
        return $folder;
    }
}

private function writeFound($relativePath, $in){
        $inf = $in->getFileInfo();

        $file = array();
        $file['name'] = $inf->getName();
        $file['path'] = $relativePath.$inf->getName();
        $file['isDir'] = $inf->getType() === "dir";
        $file['mtime'] = $inf->getMtime();
        array_push($this->data, $file);
        //$this->searchCache->putContent(json_encode($this->data));
    
}
private function search($relativePath, $folder, $query, $curDepth){
    $array = array();
    $endWell = true;
    foreach($folder->getDirectoryListing() as $in){
        $this->current = $this->current+1;
        //$this->output->writeln('in '.$in->getPath());
        
        if($in->getFileInfo()->getType() === "dir"){
            if($curDepth<30) //might be a problem in nc db
            $endWell = $this->search(($relativePath!==""?$relativePath."/":"").$in->getName()."/", $in, $query, $curDepth+1);
        }
        else if($this->current > $this->from){
            if(in_array($relativePath.$in->getName(), $this->pathArray)){
                continue;
            }
            if(strstr(strtolower($this->removeAccents($in->getName())), $query)){
                $this->writeFound($relativePath, $in);
                continue;
            }

            try {
                $zipFile = new \PhpZip\ZipFile();
                $zipFile->openFromStream($in->fopen("r"));
                try {
                    $metadata = json_decode($zipFile->getEntryContents("metadata.json"));
                    $hasFound = false;
                    if (is_object ($metadata))
                    {
                        foreach($metadata->keywords as $keyword){
                            if(strstr($this->removeAccents(strtolower($keyword)), $query)){
                                $this->writeFound($relativePath,$in);
                                $hasFound = true;
                                break;
                            }
                        }
                    }
                    if($hasFound){
                        continue;
                    }
                    
                } catch(Exception $e){
                }
                $index = $zipFile->getEntryContents("index.html");
                if(trim ($query) !== "" && strstr(strtolower($this->removeAccents($index)), $query)){
                    $this->writeFound($relativePath,$in);
                }
            } catch(\OCP\Files\NotFoundException $e) {
            } catch(\PhpZip\Exception\ZipException $e){
            } catch(Exception $e){
            }
        }
        if(time() - $this->startTime>=2){
            return false;
        }
    }
     return $endWell;
}

protected function configure() {
$this->setName('carnet:search')
->setDescription('Search')
->addArgument(
        'user_id',
        InputArgument::REQUIRED,
        'Search with specified user'
)
->addArgument(
    'query',
    InputArgument::REQUIRED,
    'Search query'
)
->addArgument(
    'root',
    InputArgument::OPTIONAL,
    'starting in path'
)
->addArgument(
    'search_id',
    InputArgument::OPTIONAL,
    'search id'
);

}


}
?>
