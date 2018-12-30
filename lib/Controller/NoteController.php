<?php
 namespace OCA\Carnet\Controller;
 use Exception;
 use OCP\App;
 use OCP\IRequest;
 use OCP\AppFramework\Controller;
 use OCP\AppFramework\Http\FileDisplayResponse;
 use OCP\AppFramework\Http\RedirectResponse;
 use OCP\AppFramework\Http\StreamResponse;
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
	public function __construct($AppName, IRequest $request, $UserId, $RootFolder, $Config){
		parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->Config = $Config;
        $this->rootFolder = $RootFolder;
        $folder = $this->Config->getUserValue($this->userId, $this->appName, "note_folder");
        //$this->Config->setUserValue($this->userId, $this->appName, "note_folder", 'Documents/QuickNote');
        if(empty($folder))
            $folder= 'Documents/QuickNote';
        try {
            $this->CarnetFolder = $RootFolder->getUserFolder($this->userId)->get($folder);
        } catch(\OCP\Files\NotFoundException $e) {
            $this->CarnetFolder = $RootFolder->getUserFolder($this->userId)->newFolder($folder);
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
        if ($path === "/" || $path === ".")
            $path = "";
        else if(substr($path, -1) !== '/' && !empty($path))
            $path .= "/";

        $data = array();
        foreach($this->CarnetFolder->get($path)->getDirectoryListing() as $in){
            $inf = $in->getFileInfo();
            $file = array();
            $file['name'] = $inf->getName();
            $file['path'] = $path.$inf->getName();
            $file['isDir'] = $inf->getType() === "dir";
            $file['mtime'] = $inf->getMtime();
            array_push($data,$file);
        }

        return $data;
    }

    /*
    * @NoAdminRequired
    * @NoCSRFRequired
    */
    public function newFolder(){
        $path = $_POST['path'];
        $this->CarnetFolder->newFolder($path);
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

    /**
    * @NoAdminRequired
    * @NoCSRFRequired
    */
	public function getNotePath() {
        return substr($this->CarnetFolder->getInternalPath(),6);
    }

    /**
    * @NoAdminRequired
    * @NoCSRFRequired
    */
   public function setNotePath() {
       if(!empty($_POST['path'])&& $this->rootFolder->getUserFolder($this->userId)->isValidPath($_POST['path'])){
            $this->Config->setUserValue($this->userId, $this->appName,"note_folder",$_POST['path']);
       }
       return substr($this->CarnetFolder->getInternalPath(),6);
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


    /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
     public function postKeywordsActions(){
        $recent = $this->getKeywordsDB();
        foreach($_POST["data"] as $action){
            array_push($recent['data'],$action);
        }
        $this->internalSaveKeywordsDB(json_encode($recent));
        return $recent;
     }
    /**
    * @NoAdminRequired
    * @NoCSRFRequired
    */
   public function getChangelog() {
       
    $changelog = file_get_contents(__DIR__."/../../CHANGELOG.md");
    $current = App::getAppInfo($this->appName)['version'];
    $last = $this->Config->getUserValue($this->userId, $this->appName, "last_changelog_version");
    if($last !== $current){
        $this->Config->setUserValue($this->userId, $this->appName, "last_changelog_version", $current);
    }
    $result = array();
    $result['changelog'] = $changelog;
    $result['shouldDisplayChangelog'] = $last !== $current;
    return $result;
   }

   /**
    * @NoAdminRequired
    * @NoCSRFRequired
    */
    public function getKeywordsDB() {  

        return json_decode($this->getKeywordsDBFile()->getContent(),true);
    }
    
   private function getKeywordsDBFile(){
       try {
           return $this->CarnetFolder->get("quickdoc/keywords/keywordsnc");
       } catch(\OCP\Files\NotFoundException $e) {
           $this->CarnetFolder->newFolder('/quickdoc/keywords', 0777, true);
           $file = $this->CarnetFolder->newFile("quickdoc/keywords/keywordsnc");
           $file->putContent("{\"data\":[]}");
           return $file;
       }
   }

   /**
   * @NoAdminRequired
   * @NoCSRFRequired
   */
   public function getUbuntuFont(){
      $font = basename($_SERVER['REQUEST_URI']);
      if($font !== ".." && $font !== "../")
        return new StreamResponse(__DIR__.'/../../templates/CarnetElectron/fonts/'.basename($_SERVER['REQUEST_URI']));
      else
        die();
   }

   /**
   * @NoAdminRequired
   * @NoCSRFRequired
   */
  public function getMaterialFont(){
    $font = basename($_SERVER['REQUEST_URI']);
    if($font !== ".." && $font !== "../")
      return new StreamResponse(__DIR__.'/../../templates/CarnetElectron/fonts/'.basename($_SERVER['REQUEST_URI']));
    else
      die();
 }

   /**
   * @NoAdminRequired
   * @NoCSRFRequired
   */
  public function mergeKeywordsDB() {
      $myDb = $this->getKeywordsDBFile();
      $hasChanged = false;
      foreach($this->CarnetFolder->get("quickdoc/keywords/")->getDirectoryListing() as $inDB){
          if($inDB->getName() === $myDb->getName()){
              continue;
          }
          $myDbContent = json_decode($myDb->getContent());
          $thisDbContent = json_decode($inDB->getContent());
          $saveDB = false;
          foreach($thisDbContent->data as $action){
             $isIn = false;
             foreach($myDbContent->data as $actionMy){
                 if($actionMy->keyword === $action->keyword && $actionMy->time === $action->time && $actionMy->path === $action->path && $actionMy->action === $action->action){
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

    private function getCacheFolder(){
        try {
            return $this->rootFolder->getUserFolder($this->userId)->get(".carnet/cache/".$this->userId);
        } catch(\OCP\Files\NotFoundException $e) {
            $folder = $this->rootFolder->getUserFolder($this->userId)->newFolder(".carnet/cache/".$this->userId, 0777, true);
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
             if($inDB->getName() === $myDb->getName()){
                 continue;
             }
             $myDbContent = json_decode($myDb->getContent());
             $thisDbContent = json_decode($inDB->getContent());
             $saveDB = false;
             foreach($thisDbContent->data as $action){
                $isIn = false;
                foreach($myDbContent->data as $actionMy){
                    if($actionMy->time === $action->time && $actionMy->path === $action->path && $actionMy->action === $action->action){
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
     public function internalSaveKeywordsDB($str) {
        $this->getKeywordsDBFile()->putContent($str);
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
        return $this->internalPostActions($_POST["data"]);
     }

     public function internalPostActions($actions){
        $recent = $this->getRecent();
        foreach($actions as $action){
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
        if ($path === "/" || $path === ".")
            $path = "";
        else if(substr($path, -1) !== '/' AND !empty($path))
            $path .= "/";
        $list = $this->CarnetFolder->get($path)->getDirectoryListing();
        $un = "untitled";
        $name = "";
        $i = 0;
        $continue = true;
        while($continue){
            $continue = false;
            $name = $un.(($i === 0)?"":" ".$i).".sqd";
            foreach($list as $in){
                if($in->getName() === $name){
                    $continue = true;
                    break;
                }
            }
            $i++;
        }
        return $path.$name;
        
     }

      /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
	 public function updateMetadata($path, $metadata){
    
        $tmppath = tempnam(sys_get_temp_dir(), uniqid().".zip");
        $tmppath2 = tempnam(sys_get_temp_dir(), uniqid().".zip");
        if(!file_put_contents($tmppath, $this->CarnetFolder->get($path)->fopen("r")))
            return;

        $zipFile = new \PhpZip\ZipFile();
        $zipFile->openFromStream(fopen($tmppath, "r")); //issue with encryption when open directly + unexpectedly faster to copy before Oo'
        $zipFile->addFromString("metadata.json", $metadata, \PhpZip\ZipFile::METHOD_DEFLATED);
        $zipFile->saveAsFile($tmppath2);
        $tmph = fopen($tmppath2, "r");
        if($tmph){
            try{
                $file = $this->CarnetFolder->get($path);
                $file->putContent($tmph);   
            } catch(\OCP\Files\NotFoundException $e) {
            }
            fclose($tmph);
        } else 
            throw new Exception('Unable to create Zip');
       unlink($tmppath);	
       unlink($tmppath2);	

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
                $tmppath = tempnam(sys_get_temp_dir(), uniqid().".zip");
                file_put_contents($tmppath, $this->CarnetFolder->get($path)->fopen("r"));
                 $zipFile = new \PhpZip\ZipFile();
                 $zipFile->openFromStream(fopen($tmppath, "r")); //issue with encryption when open directly + unexpectedly faster to copy before Oo'
                
                 $array[$path] = array();
                 try{
                    $array[$path]['metadata'] = json_decode($zipFile->getEntryContents("metadata.json"));
                 } catch(\PhpZip\Exception\ZipNotFoundEntry $e){
                    
                 }
                 try{
                    
                    $array[$path]['shorttext'] = mb_substr(trim(preg_replace('#<[^>]+>#', ' ', $zipFile->getEntryContents("index.html"))),0, 150);
                    $i=0;
                    try{
                        foreach($zipFile->getListFiles() as $f){
                            if(substr($f, 0, strlen("data/preview")) === "data/preview"){

                                $array[$path]['previews'][$i] = "data:image/jpeg;base64,".base64_encode($zipFile->getEntryContents($f));
                                $i++;
                                if($i>2)
                                    break;
                            }
                            
                        }
                    }
                    catch(\PhpZip\Exception\ZipNotFoundEntry $e){
                        
                    }
                } catch(\PhpZip\Exception\ZipNotFoundEntry $e){
                    $array[$path]['shorttext'] = "";
                    
                }
                 unlink($tmppath);
			} catch(\OCP\Files\NotFoundException $e) {
            }
           
		}
		 return $array;
     }

     /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
	 public function search($from, $query){
        try {
            $this->getCacheFolder()->get("carnet_search")->delete();
        } catch(\OCP\Files\NotFoundException $e) {
            
        }
        shell_exec('php occ carnet:search '.$this->userId.' '.escapeshellcmd($query).' '.escapeshellcmd($from).'> /dev/null 2>/dev/null &');
     }

     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getSearchCache(){
        try {
            $c = json_decode($this->getCacheFolder()->get("carnet_search")->getContent());
            if($c)
                return $c;
        } catch(\OCP\Files\NotFoundException $e) {  
        } catch(\OCP\Lock\LockedException $e){
            sleep(2);
            $c = json_decode($this->getCacheFolder()->get("carnet_search")->getContent());
            if($c)
                return $c;
        }
        return array();
    }

     /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
	 public function deleteNote($path){
		$this->CarnetFolder->get($path)->delete();
     }

     /**
      * @NoAdminRequired
      * @NoCSRFRequired
      */
    public function moveNote($from, $to){     
        if($this->CarnetFolder->nodeExists($to)){
            throw new Exception("Already exists");
        }
       $this->CarnetFolder->get($from)->move($this->CarnetFolder->getFullPath($to));
       $actions = array();
       $actions[0] = array();
       $actions[0]['action'] = "move";
       $actions[0]['path'] = $from;
       $actions[0]['newPath'] = $to;
       $actions[0]['time'] = time();
       $this->internalPostActions($actions);
    }

      /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */

     public function getEditorUrl(){
         return "./writer";
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


     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteMediaFromOpenNote($id){
        $cache = $this->getCacheFolder();
        $folder = $cache->get("currentnote".$id);
        
        $folder->get("data/".$_GET['media'])->delete();
        try{
            $folder->get("data/preview_".$_GET['media'].".jpg")->delete();
        } catch(\OCP\Files\NotFoundException $e) {
        }
        $this->saveOpenNote($_GET['path'],$id);
        return $this->listMediaOfOpenNote($id);

    }
     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
     public function addMediaToOpenNote($id){
        $cache = $this->getCacheFolder();
        $folder = $cache->get("currentnote".$id);
        
        try{
            $data = $folder->get("data");
        } catch(\OCP\Files\NotFoundException $e) {
            $data = $folder->newFolder("data");
        }
        $fileIn = fopen($_FILES['media']['tmp_name'][0],"r");
        if (!$fileIn) {
            throw new Exception('Media doesn\'t exist');
        } else {
            $fileOut = $data->newFile($_FILES['media']['name'][0]);
            $fileOut->putContent($fileIn);
            if(@is_array(getimagesize($_FILES['media']['tmp_name'][0]))){
                $fn = $_FILES['media']['tmp_name'][0];
                $size = getimagesize($fn);
                $ratio = $size[0]/$size[1]; // width/height
                if( $ratio > 1) {
                    $width = 200;
                    $height = 200/$ratio;
                }
                else {
                    $width = 200*$ratio;
                    $height = 200;
                }
                $src = imagecreatefromstring(file_get_contents($fn));
                $dst = imagecreatetruecolor($width,$height);
                imagecopyresampled($dst,$src,0,0,0,0,$width,$height,$size[0],$size[1]);
                imagedestroy($src);
                $fileOut = $data->newFile("preview_".$_FILES['media']['name'][0].".jpg");
                imagejpeg($dst,$fileOut->fopen("w"));
                imagedestroy($dst);
            }
            fclose($fileIn);
        }
        $this->saveOpenNote($_POST['path'],$id);
        return $this->listMediaOfOpenNote($id);
     }

     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
     public function listMediaOfOpenNote($id){
        $cache = $this->getCacheFolder();
        $folder = $cache->get("currentnote".$id);
        $media = array();
        try{
            $data = $folder->get("data");
            foreach($data->getDirectoryListing() as $in){
                if(substr($in->getName(), 0, strlen("preview_")) !== "preview_")
                    array_push($media,"note/open/".$id."/getMedia/".$in->getName());
            }
        } catch(\OCP\Files\NotFoundException $e) {
            
        }
        return $media;
     }

     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
     public function getMediaOfOpenNote($id, $media){
        $cache = $this->getCacheFolder();
        $folder = $cache->get("currentnote".$id);
        $data = $folder->get("data");
        $f = $data->get($media);
        $r = new FileDisplayResponse($f);
        $r->addHeader("Content-Disposition", "attachment");
        $r->addHeader("Content-Type", $f->getMimeType());

        return $r;
     }

     private function saveOpenNote($path,$id){
        $cache = $this->getCacheFolder();
        $folder = $cache->get("currentnote".$id);
        $zipFile = new MyZipFile();
        $this->addFolderContentToArchive($folder,$zipFile,"");
        $file = $this->CarnetFolder->newFile($path);
        //tried to do with a direct fopen on $file but lead to bad size on nextcloud
        $tmppath = tempnam(sys_get_temp_dir(), uniqid().".sqd");
        $zipFile->saveAsFile($tmppath);
        $tmph = fopen($tmppath, "r");
        if($tmph){
            try{
                $this->CarnetFolder->get($path)->delete();
            } catch(\OCP\Files\NotFoundException $e) {
            }
            $file->putContent($tmph);
            fclose($tmph);
        } else 
            throw new Exception('Unable to create Zip');

        unlink($tmppath);
     } 

     private function addFolderContentToArchive($folder, $archive, $relativePath){
        foreach($folder->getDirectoryListing() as $in){
            $inf = $in->getFileInfo();
            $path = $relativePath.$inf->getName();
            if($inf->getType() === "dir"){
                $archive->addEmptyDir($path);
                $this->addFolderContentToArchive($in, $archive, $path."/");
            }else {
                $archive->addFromStream($in->fopen("r"), $path, \PhpZip\ZipFile::METHOD_DEFLATED);
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
        try{
            if($folder !== null)
                $folder->delete();
        }
        catch(\BadMethodCallException $e){
        }
        $folder = $cache->newFolder("currentnote".$editUniqueID);
        try{
            $tmppath = tempnam(sys_get_temp_dir(), uniqid().".zip");
            file_put_contents($tmppath,$this->CarnetFolder->get($path)->fopen("r"));

            $zipFile = new MyZipFile();
            $zipFile->openFile($tmppath);
            foreach($zipFile as $entryName => $contents){
                if($contents === "" AND $zipFile->isDirectory($entryName)){
                    $folder->newFolder($entryName);
                }
                else if($contents !== "" && $contents !== NULL){
                    if($entryName === "index.html"){
                        $data['html'] = $contents;
                    } else if($entryName === "metadata.json"){
                        $data['metadata'] = json_decode($contents);
                    }
                    $parent = dirname($entryName);
                    if($parent !== "." && !$folder->nodeExists($parent)){
                        $folder->newFolder($parent);
                    }
                    $folder->newFile($entryName)->putContent($contents);
                }
            }
            unlink($tmppath);
        } catch(\OCP\Files\NotFoundException $e) {
            $data["error"] = "not found";
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
     * @NoCSRFRequired
     */
     public function isFirstRun(){
        $isFirst = $this->Config->getUserValue($this->userId, $this->appName, "is_first_run",1);
        $this->Config->setUserValue($this->userId, $this->appName, "is_first_run", 0);
        return $isFirst;
     }
     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
     public function downloadArchive(){

        return new RedirectResponse("../../../../../index.php/apps/files/ajax/download.php?files=".$this->getNotePath());
     }

     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
     public function importNote(){
    
     }


     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getAppThemes(){
        $root = \OCP\Util::linkToAbsolute($this->appName,"templates")."/CarnetElectron/css/";
        if(strpos($root,"http://") === 0 && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
            //should be https...
            $root = "https".substr($root,strlen("http"));
        }
        return json_decode('[{"name":"Carnet", "path":"'.$root.'carnet", "preview":"'.$root.'carnet/preview.png"}, {"name":"Dark", "path":"'.$root.'dark", "preview":"'.$root.'dark/preview.png"}]');
    }
     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
     public function setAppTheme($url){
         if(strpos($url, '/') !== false && strpos($url, 'http') !==0)
            throw new Exception("forbidden");
        $meta = json_decode(file_get_contents($url."/metadata.json"),true);
        $browser = array();
        $editor = array();
        $settings = array();
        foreach($meta['browser'] as $css){
            array_push($browser, $url."/".$css);
        }
        $this->Config->setUserValue($this->userId, $this->appName,"css_browser", json_encode($browser));
        foreach($meta['editor'] as $css){
            array_push($editor, $url."/".$css);
        }
        $this->Config->setUserValue($this->userId, $this->appName,"css_editor", json_encode($editor));
        foreach($meta['settings'] as $css){
            array_push($settings, $url."/".$css);
        }
        $this->Config->setUserValue($this->userId, $this->appName,"css_settings", json_encode($settings));
        
     }

     /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
      public function getEditorCss(){
        $css = $this->Config->getUserValue($this->userId, $this->appName, "css_editor");
        if(empty($css))
          $css = "[]";
        return json_decode($css);
      }

      /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getBrowserCss(){
        $css = $this->Config->getUserValue($this->userId, $this->appName, "css_browser");
        if(empty($css))
          $css = "[]";
        return json_decode($css);
    }


      /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getSettingsCss(){
        $css = $this->Config->getUserValue($this->userId, $this->appName, "css_settings");
        if(empty($css))
          $css = "[]";
        return json_decode($css);
    }


 }
?>