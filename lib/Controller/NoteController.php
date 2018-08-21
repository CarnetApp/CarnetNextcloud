<?php
 namespace OCA\Carnet\Controller;
 use OCP\IRequest;
 use OCP\AppFramework\Controller;
 //require_once 'vendor/autoload.php';

 class MyZipFile extends \PhpZip\ZipFile {
     public function getInputStream(){
        return $this->inputStream;
     }
 }
 class NoteController extends Controller {
	private $userId;
    private $bla;
    private $storage;
    private $CarnetFolder;
    private $appFolder;
	public function __construct($AppName, IRequest $request, $UserId, $RootFolder, $AppFolder){
		parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->appFolder= $AppFolder;
        try {
            $this->CarnetFolder = $RootFolder->getUserFolder($this->userId)->get('Documents/QuickNote');
        } catch(\OCP\Files\NotFoundException $e) {
            $this->CarnetFolder = $RootFolder->getUserFolder($this->userId)->newFolder('Documents/QuickNote');
        }
       // \OC_Util::tearDownFS();
       // \OC_Util::setupFS($UserId);
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function listDir() {
        $path = $_GET['path'];
        if ($path == "/" || $path == ".")
            $path = "";
        else if(substr($path, -1) != '/' && !empty($path))
            $path .= "/";

        $data = array();
        foreach($this->CarnetFolder->get($path)->getDirectoryListing() as $in){
            $inf = $in->getFileInfo();
            $file = array();
            $file['name'] = $inf->getName();
            $file['path'] = $path.$inf->getName();
            $file['isDir'] = $inf->getType() == "dir";
            $file['mtime'] = $inf->getMtime();
            array_push($data,$file);
        }

        return $data;
    }
    /**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
        $data = array();
        $note1 = array();
        $note2 = array();

        $note1['path'] = $path;
        array_push($data, $note1);
        $note2['path'] = "path2".$this->CarnetFolder->getPath();
        array_push($data, $note2);

        return $data;
    }

    /**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getRecent() {
        

        return json_decode($this->getRecentFile()->getContent(),true);
    }

    private function getRecentFile(){
        try {
            return $this->CarnetFolder->get("quickdoc/recentdb/recentnc");
        } catch(\OCP\Files\NotFoundException $e) {
            $this->CarnetFolder->newFolder('/quickdoc/recentdb', 0777, true);
            $file = $this->CarnetFolder->newFile("quickdoc/recentdb/recentnc");
            $file->putContent("{\"data\":[]}");
            return $file;
        }
    }

    private function getCacheFolder(){
        try {
            return $this->appFolder->get("Carnet/cache/".$this->userId);
        } catch(\OCP\Files\NotFoundException $e) {
            $folder = $this->appFolder->newFolder("Carnet/cache/".$this->userId, 0777, true);
            return $folder;
        }
    }

    /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
     public function mergeRecentDB() {
         $myDb = $this->getRecentFile();
         $hasChanged = false;
         foreach($this->CarnetFolder->get("quickdoc/recentdb/")->getDirectoryListing() as $inDB){
             if($inDB->getName() == $myDb->getName()){
                 continue;
             }
             $myDbContent = json_decode($myDb->getContent());
             $thisDbContent = json_decode($inDB->getContent());
             $saveDB = false;
             foreach($thisDbContent->data as $action){
                $isIn = false;
                foreach($myDbContent->data as $actionMy){
                    if($actionMy->time == $action->time && $actionMy->path == $action->path && $actionMy->action == $action->action){
                        $isIn = true;
                        break;
                     }         
                 }
                 if(!$isIn){
                    $hasChanged = true;
                    $saveDB = true;
                    array_push($myDbContent->data,$action);
                 }
             }
             if($saveDB){
                usort($myDbContent->data, function($a, $b) {
                    return $a->time <=> $b->time;
                });
                $myDb->putContent(json_encode($myDbContent));
             }
         }
         return $hasChanged;
     }

     /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
     public function saveRecent($id) {
        // check if file exists and write to it if possible
        try {
            try {
                $file = $this->storage->get('/myfile.txt');
            } catch(\OCP\Files\NotFoundException $e) {
                $this->storage->touch('/myfile.txt');
                $file = $this->storage->get('/myfile.txt');
            }

            // the id can be accessed by $file->getId();
            $file->putContent($content);

        } catch(\OCP\Files\NotPermittedException $e) {
            // you have to create this exception by yourself ;)
            throw new StorageException('Cant write to file');
        }
     }

     /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
     public function internalSaveRecent($str) {
        $this->getRecentFile()->putContent($str);
     }

     /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
     public function postActions(){
        $recent = $this->getRecent();
        foreach($_POST["data"] as $action){
            array_push($recent['data'],$action);
        }
        $this->internalSaveRecent(json_encode($recent));
        return $recent;
     }

     /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
     public function createNote(){
        $path = $_GET['path'];
        if ($path == "/" || $path == ".")
            $path = "";
        else if(substr($path, -1) != '/' AND !empty($path))
            $path .= "/";
        $list = $this->CarnetFolder->get($path)->getDirectoryListing();
        $un = "untitled";
        $name = "";
        $i = 0;
        $continue = true;
        while($continue){
            $continue = false;
            $name = $un.(($i == 0)?"":" ".$i).".sqd";
            foreach($list as $in){
                if($in->getName() == $name){
                    $continue = true;
                    break;
                }
            }
            $i++;
        }
        return $name;
        
     }

     /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
	 public function getMetadata($paths){
		$array = array();
		$pathsAr = explode(",",$paths);

		foreach($pathsAr as $path){
			if(empty($path))
				continue;
			try {
				 $zipFile = new \PhpZip\ZipFile();
				 $zipFile->openFromStream($this->CarnetFolder->get($path)->fopen("r"));
                 $array[$path] = array();
                 try{
                    $array[$path]['metadata'] = json_decode($zipFile->getEntryContents("metadata.json"));
                 } catch(Exception $e){
                    
                 }
                 try{
                    $array[$path]['shorttext'] = mb_substr(trim(strip_tags($zipFile->getEntryContents("index.html"))),0, 100);
                } catch(\PhpZip\Exception\ZipNotFoundEntry $e){
                    $array[$path]['shorttext'] = "";
                }
			} catch(\OCP\Files\NotFoundException $e) {
			}
		}
		 return $array;
     }
     
     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
     public function saveTextToOpenNote(){
        $id = $_POST['id'];
        $cache = $this->getCacheFolder();
        $folder = $cache->get("currentnote".$id);
        try{
            $file = $folder->get("index.html");
        } catch(\OCP\Files\NotFoundException $e) {
            $file = $folder->newFile("index.html");
        }
        $file->putContent($_POST['html']);
        try{
            $file = $folder->get("metadata.json");
        } catch(\OCP\Files\NotFoundException $e) {
            $file = $folder->newFile("metadata.json");
        }
        $file->putContent($_POST['metadata']);

        $this->saveOpenNote($_POST['path'],$id);
     }

     private function saveOpenNote($path,$id){
        $cache = $this->getCacheFolder();
        $folder = $cache->get("currentnote".$id);
        $zipFile = new MyZipFile();
        $this->addFolderContentToArchive($folder,$zipFile,"");
        try{
            $file = $this->CarnetFolder->get($path);
        } catch(\OCP\Files\NotFoundException $e) {
            $file = $this->CarnetFolder->newFile($path);
        }
        $zipFile->saveAsStream($file->fopen("w"));
     } 

     private function addFolderContentToArchive($folder, $archive, $relativePath){
        foreach($folder->getDirectoryListing() as $in){
            $inf = $in->getFileInfo();
            $path = $relativePath.$inf->getName();
            if($inf->getType() == "dir"){
                $archive->addEmptyDir($path);
                $this->addFolderContentToArchive($in, $archive, $path."/");
            }else {
                $archive->addFromStream($in->fopen("r"), $path, \PhpZip\ZipFile::METHOD_STORED);
            }

        }
     }

     private function getCurrentnoteDir(){
        $cache = $this->getCacheFolder();
        //find current note folder
        foreach($cache->getDirectoryListing() as $in){
             if(substr($in->getName(), 0, strlen("currentnote")) === "currentnote"){
                 return $in;
             }
        }
        return null;
     }
     
     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
     public function openNote(){
        $editUniqueID = uniqid();
        $data = array();
        $path = $_GET['path'];
        $cache = $this->getCacheFolder();
        /*
            because of an issue with nextcloud, working with a single currentnote folder is impossible...
        */
        $folder = $this->getCurrentnoteDir();
        if($folder != null)
            $folder->delete();
        
        $folder = $cache->newFolder("currentnote".$editUniqueID);
        try{
            $tmppath = getcwd()."/".uniqid().".zip";
            file_put_contents($tmppath,$this->CarnetFolder->get($path)->fopen("r"));

            $zipFile = new MyZipFile();
            $zipFile->openFile($tmppath);
            foreach($zipFile as $entryName => $contents){
                if($contents == "" AND $zipFile->isDirectory($entryName)){
                    $folder->newFolder($entryName);
                }
                else{
                    if($entryName == "index.html"){
                        $data['html'] = $contents;
                    } else if($entryName == "metadata.json"){
                        $data['metadata'] = json_decode($contents);
                    }
                    $folder->newFile($entryName)->putContent($contents);
                }
            }
            unlink($tmppath);
        } catch(\OCP\Files\NotFoundException $e) {
        }
        $data['id'] = $editUniqueID;
        return $data; 

     }
     /**
      * @NoAdminRequired
      *
      * @param string $title
      * @param string $content
      */
     public function create($title, $content) {
         // empty for now
     }

     /**
      * @NoAdminRequired
      *
      * @param int $id
      * @param string $title
      * @param string $content
      */
     public function update($id, $title, $content) {
         // empty for now
     }

     /**
      * @NoAdminRequired
      *
      * @param int $id
      */
     public function destroy($id) {
         // empty for now
     }

 }
?>