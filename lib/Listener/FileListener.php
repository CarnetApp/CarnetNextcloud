<?php
namespace OCA\Carnet\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\FileInfo;
use Psr\Log\LoggerInterface;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\IDBConnection;
use OCA\Carnet\Misc\CacheManager;
use OCA\Carnet\Misc\NoteUtils;
use OCA\Carnet\Controller\NoteController;
use OCP\Files\IRootFolder;

class FileListener implements IEventListener {

    private $folder;
    private $carnetFolder;

    public function __construct(private LoggerInterface $logger,  private IUserSession $userSession, private IConfig $Config, private IDBConnection $db, private IRootFolder $rootFolder) {
        $this->logger = $logger;
        $this->folder = $this->Config->getUserValue($this->userSession->getUser()->getUID(), "carnet", "note_folder");
        $UserFolder = $this->rootFolder->getUserFolder($this->userSession->getUser()->getUID());
        if(empty($this->folder))
            $this->folder= NoteUtils::$defaultCarnetNotePath;
        try{
            $this->carnetFolder = $UserFolder->get($this->folder);
        } catch (\OCP\Files\NotFoundException $e){
            $this->carnetFolder = null;
        }
    }

    public function handle(Event $event): void {
        if (!($event instanceof NodeDeletedEvent) && !($event instanceof NodeWrittenEvent)) {
            return;
        }

        $node = $event->getNode();
        $path = $node->getPath(); 
        $name = $node->getName(); 
        try {
            if ($event instanceof NodeDeletedEvent){
                $this->postDelete($node);
            }
            else if ($event instanceof NodeWrittenEvent) {
                $this->postWrite($node);
            }
  
        } catch (\Exception $e) {
            $this->logger->error('FileListener: an error happened on ' . $name . ' : ' . $e->getMessage());
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

    private function getSQDNode($node){
        if(substr($node->getName(), -3) === "sqd")
            return $node;
        $parent = $node->getParent();
        if($parent != NULL){
            if($parent->getName() === "data"){
                $parent = $parent->getParent();
            }
            if(substr($parent->getName(), -3) === "sqd"){
                return $parent;
        }
        }
        return false;
    }

    private function isMine($node){
        if(substr($node->getPath(), 0, strlen($this->carnetFolder->getPath())) === $this->carnetFolder->getPath()){
            return true;
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
                    $node = $this->getSQDNode($node);
                    if(!$node)
                        return;
                    $relativePath = $this->getRelativePath($node->getPath());
                    $cacheManager = new CacheManager($this->db, $this->carnetFolder);
                    $utils = new NoteUtils();
                    $metadata = $utils->getMetadata($this->carnetFolder, $relativePath);
                    $cacheManager->addToCache($relativePath, $metadata, $metadata['lastmodfile'], isset($metadata['text']) ? $metadata['text'] : '');
                } catch(\PhpZip\Exception\ZipException $e){

            }
        }
    }
}