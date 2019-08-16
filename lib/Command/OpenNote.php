<?php

namespace OCA\Carnet\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCA\Carnet\Misc\CacheManager;
use OCP\IDBConnection;

class OpenNote extends Command {
    private $output;
    private $appName;
    private $CarnetFolder;
    private $Config;
    private $rootFolder;
    private $searchCache;
    /**
         * @param string $appName
         * @param IRootFolder $rootFolder
    */
public function __construct($AppName, $RootFolder,  $Config, IDBConnection $IDBConnection){
    parent::__construct();
    $this->appName = $AppName;
    $this->Config = $Config;
    $this->db = $IDBConnection;
    $this->rootFolder = $RootFolder;
}

private function getCacheFolder(){
        try {
            return $this->rootFolder->getUserFolder($this->userId)->get(".carnet/cache/".$this->userId);
        } catch(\OCP\Files\NotFoundException $e) {
            $folder = $this->rootFolder->getUserFolder($this->userId)->newFolder(".carnet/cache/".$this->userId, 0777, true);
            return $folder;
        }
}

/*
* @param InputInterface $input
* @param OutputInterface $output
* @return int
*/
protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output = $output;
    $this->userId = $input->getArgument('user_id');
    $folder = $this->Config->getUserValue($this->userId , $this->appName, "note_folder");
    
    if(empty($folder))
        $folder= 'Documents/QuickNote';
    try {
        $this->CarnetFolder = $this->rootFolder->getUserFolder($this->userId)->get($folder);
    } catch(\OCP\Files\NotFoundException $e) {
        $this->CarnetFolder = $this->rootFolder->getUserFolder($this->userId)->newFolder($folder);
    }
    
    $path = $input->getArgument('path');
    $editUniqueID = $input->getArgument('session_id');
    $cache = $this->getCacheFolder();
    /*
        because of an issue with nextcloud, working with a single currentnote folder is impossible...
    */    
    foreach($cache->getDirectoryListing() as $in){
        if(substr($in->getName(), 0, strlen("currentnote")) === "currentnote"){
            $in->delete();
        }
    }
    $folder = $cache->newFolder("currentnote".$editUniqueID);
    try{
        $tmppath = tempnam(sys_get_temp_dir(), uniqid().".zip");
        file_put_contents($tmppath,$this->CarnetFolder->get($path)->fopen("r"));
        $zipFile = new \PhpZip\ZipFile();
        $zipFile->openFile($tmppath);
        foreach($zipFile as $entryName => $contents){
        if($entryName === ".extraction_finished")
        continue;    
        if($contents === "" AND $zipFile->isDirectory($entryName)){
            $folder->newFolder($entryName);
        }
        else if($contents !== "" && $contents !== NULL){
            $parent = dirname($entryName);
            if($parent !== "." && !$folder->nodeExists($parent)){
                $folder->newFolder($parent);
            }
            $folder->newFile($entryName)->putContent($contents);
        }
    }
    unlink($tmppath);
    } catch(\OCP\Files\NotFoundException $e) {
        $this->output->writeln('not found in '.$e);
    }
    $folder->newFile(".extraction_finished");
}      

protected function configure() {
$this->setName('carnet:opennote')
->setDescription('OpenNote')
->addArgument(
        'user_id',
        InputArgument::REQUIRED,
        'Open with specified user'
)
->addArgument(
    'path',
    InputArgument::REQUIRED,
    'Relative path of the note'
)
->addArgument(
    'session_id',
    InputArgument::REQUIRED,
    'session id'
);

}


}
?>
