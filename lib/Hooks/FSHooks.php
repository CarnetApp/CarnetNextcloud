<?php
namespace OCA\Carnet\Hooks;
use OCP\IUserManager;
use OCA\Carnet\Misc\CacheManager;
use OCA\Carnet\Misc\NoteUtils;
use OCA\Carnet\Controller\NoteController;
use OCP\IDBConnection;

class FSHooks {
    private $userFolder;
    private $Config;
    private $userId;
    private $appName;
    private $db;
    private $folder;
    private $carnetFolder;
    public function __construct($UserFolder, $UserId, $Config, $appName, IDBConnection $connection){
        $this->userFolder = $UserFolder;
        $this->Config = $Config;
        $this->userId = $UserId;
        $this->appName = $appName;
        $this->db = $connection;
        $this->folder = $this->Config->getUserValue($this->userId, $this->appName, "note_folder");
        if(empty($folder))
            $folder= 'Documents/QuickNote';
        $this->carnetFolder = $UserFolder->get($folder);
    }
    
    public function postWrite($node) {
        try{
            if(substr($node->getName(), -3) === "sqd"){ // to avoid getting carnet's path each time a file is writen
                //we check if is in our path
                        
                if(substr($node->getPath(), 0, strlen($this->carnetFolder->getPath())) === $this->carnetFolder->getPath()){
                    $relativePath = substr($node->getPath(), strlen($this->carnetFolder->getPath()));
                    if(substr($relativePath, 0, 1) === "/")
                        $relativePath = substr($relativePath, 1); 
                    /*if(NoteController::$lastWrite === $node->getPath()){
                        return; //was already handled in save
                    }*/
                    $cacheManager = new CacheManager($this->db);
                    $utils = new NoteUtils();
                    $metadata = $utils->getMetadata($this->carnetFolder, $relativePath);
                    $cacheManager->addToCache($relativePath, $metadata, $metadata['lastmodfile']);
                }
            }
        } catch(\PhpZip\Exception\ZipException $e){

        }
        
        
    }

    public function postWritePath($node) {
        if($node != Null)
        file_put_contents("dump.txt", $node."\n", FILE_APPEND);
    }
}

?>