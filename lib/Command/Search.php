<?php

namespace OCA\Carnet\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;


class Search extends Command {
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
public function __construct($AppName, $RootFolder, $AppFolder, $Config){
    parent::__construct();
    $this->appName = $AppName;
    $this->Config = $Config;
    $this->rootFolder = $RootFolder;
    $this->appFolder = $AppFolder;
}
/**
* @param InputInterface $input
* @param OutputInterface $output
* @return int
*/
protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output = $output;
    $this->userId = $input->getArgument('user_id');
    try {
        $this->getCacheFolder()->get("carnet_search")->delete();
    } catch(\OCP\Files\NotFoundException $e) {
        
    }
    $this->searchCache = $this->getCacheFolder()->newFile("carnet_search");
    $this->searchCache->putContent("[");
    $folder = $this->Config->getUserValue($userId , $this->appName, "note_folder");
    
    if(empty($folder))
        $folder= 'Documents/QuickNote';
    try {
        $this->CarnetFolder = $this->rootFolder->getUserFolder($this->userId)->get($folder);
    } catch(\OCP\Files\NotFoundException $e) {
        $this->CarnetFolder = $this->rootFolder->getUserFolder($this->userId)->newFolder($folder);
    }
    $output->writeln('starting '.$this->appName.' user '.$input->getArgument('user_id'));

    $output->writeln('searching '.$input->getArgument('query')." in ".$this->CarnetFolder->getFullPath($input->getArgument('root')));
    $path = $input->getArgument('root');
    if (!empty($path) && substr($path, -1) == '/' || $path == ".")
        $path = substr($path, -1);
    $this->search($path, $this->CarnetFolder->get($path),$input->getArgument('query'),0);
    $this->searchCache->putContent($this->searchCache->getContent()."\"end_of_search\"]");
}

private function getCacheFolder(){
    try {
        return $this->appFolder->get("Carnet/cache/".$this->userId);
    } catch(\OCP\Files\NotFoundException $e) {
        $folder = $this->appFolder->newFolder("Carnet/cache/".$this->userId, 0777, true);
        return $folder;
    }
}

private function writeFound($relativePath, $in){
    $this->output->writeln('found in '.$in->getPath());
    if($this->searchCache){
        $inf = $in->getFileInfo();
        $file = array();
        $file['name'] = $inf->getName();
        $file['path'] = $relativePath."/".$inf->getName();
        $file['isDir'] = $inf->getType() == "dir";
        $file['mtime'] = $inf->getMtime();
        $this->searchCache->putContent($this->searchCache->getContent().json_encode($file).",");
    }
}
private function search($relativePath, $folder, $query, $curDepth){
    $array = array();

    foreach($folder->getDirectoryListing() as $in){
        //$this->output->writeln('in '.$in->getPath());
        
        if($in->getFileInfo()->getType() == "dir"){
            if($curDepth<30) //might be a problem in nc db
            $this->search(($relativePath!=""?relativePath."/":"").$in->getName(), $in, $query, $curDepth+1);

        }
        else{
            if(strstr($in->getName(), $query)){
                $this->writeFound($relativePath, $in);
                continue;
            }
            try {
                $zipFile = new \PhpZip\ZipFile();
                $zipFile->openFromStream($in->fopen("r"));
                $index = $zipFile->getEntryContents("index.html");
                if(strstr($index, $query)){
                    $this->writeFound($relativePath,$in);
                }
            } catch(\OCP\Files\NotFoundException $e) {
            } catch(\PhpZip\Exception\ZipException $e){
            }
        }
    }
     return $array;
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