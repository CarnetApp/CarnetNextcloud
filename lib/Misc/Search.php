<?php

namespace OCA\Carnet\Misc;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCA\Carnet\Misc\CacheManager;
use OCA\Carnet\Misc\NoteUtils;

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


public function startSearch($query, $from) {
    $query = NoteUtils::removeAccents($query);
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
        
        if($in->getFileInfo()->getType() === "dir" && !NoteUtils::isNote($in->getName())){
            if($curDepth<30) //might be a problem in nc db
            $endWell = $this->search(($relativePath!==""?$relativePath."/":"").$in->getName()."/", $in, $query, $curDepth+1);
        }
        else if($this->current > $this->from){
            if(in_array($relativePath.$in->getName(), $this->pathArray)){
                continue;
            }
            if(strstr(strtolower(NoteUtils::removeAccents($in->getName())), $query)){
                $this->writeFound($relativePath, $in);
                continue;
            }

            try {
                if($in->getFileInfo()->getType() === "dir"){
                    $metadata = json_decode($in->get("metadata.json")->getContent());
                    $hasFound = false;
                    if (is_object ($metadata))
                    {
                        foreach($metadata->keywords as $keyword){
                            if(strstr(NoteUtils::removeAccents(strtolower($keyword)), $query)){
                                $this->writeFound($relativePath,$in);
                                $hasFound = true;
                                break;
                            }
                        }
                    }
                    if($hasFound){
                        continue;
                    }
                    $index = $in->get("index.html")->getContent();
                    if(trim ($query) !== "" && strstr(strtolower(NoteUtils::removeAccents($index)), $query)){
                        $this->writeFound($relativePath,$in);
                    }
                }
                else {
                    $zipFile = new \PhpZip\ZipFile();
                    $zipFile->openFromStream($in->fopen("r"));
                    try {
                        $metadata = json_decode($zipFile->getEntryContents("metadata.json"));
                        $hasFound = false;
                        if (is_object ($metadata))
                        {
                            foreach($metadata->keywords as $keyword){
                                if(strstr(NoteUtils::removeAccents(strtolower($keyword)), $query)){
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
                    if(trim ($query) !== "" && strstr(strtolower(NoteUtils::removeAccents($index)), $query)){
                        $this->writeFound($relativePath,$in);
                    }
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
