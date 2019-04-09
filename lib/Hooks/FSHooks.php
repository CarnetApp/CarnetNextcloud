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
        try{
            $this->carnetFolder = $UserFolder->get($folder);
        } catch (\OCP\Files\NotFoundException $e){
            $this->carnetFolder = null;
        }
    }
    
    public function postDelete($node){
        if($this->carnetFolder == null)
            return;
        if($this->isMine($node)){
            $cacheManager = new CacheManager($this->db, $this->carnetFolder);
            $cacheManager->deleteFromCache($this->getRelativePath($node->getPath()));
        }   
    }

    private function isMine($node){
        if(substr($node->getName(), -3) === "sqd"){ // to avoid getting carnet's path each time a file is writen
            //we check if is in our path
                    
            if(substr($node->getPath(), 0, strlen($this->carnetFolder->getPath())) === $this->carnetFolder->getPath()){
                return true;
            }
        }
        return false;
    }

    private function getRelativePath($fullPath){
        $relativePath = substr($fullPath, strlen($this->carnetFolder->getPath()));
        if(substr($relativePath, 0, 1) === "/")
            $relativePath = substr($relativePath, 1); 
        return $relativePath;
    }

    public function postWrite($node) {
        if($this->carnetFolder == null || substr($_SERVER['REQUEST_URI'], -strlen('carnet/note/saveText')) === 'carnet/note/saveText')
        { //cache is handled on save
            return;
        }
        if($this->isMine($node)){
                try{
                
                /*if(NoteController::$lastWrite === $node->getPath()){
                    return; //was already handled in save
                }*/
                $relativePath = $this->getRelativePath($node->getPath());
                $cacheManager = new CacheManager($this->db, $this->carnetFolder);
                $utils = new NoteUtils();
                $metadata = $utils->getMetadata($this->carnetFolder, $relativePath);
                $cacheManager->addToCache($relativePath, $metadata, $metadata['lastmodfile']);
            } catch(\PhpZip\Exception\ZipException $e){

            }
        }
    }

    public function postWritePath($node) {
        if($node != Null)
        file_put_contents("dump.txt", $node."\n", FILE_APPEND);
    }
}

?>