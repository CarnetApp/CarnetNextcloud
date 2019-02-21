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
public function __construct($AppName, $RootFolder,  $Config){
    parent::__construct();
    $this->appName = $AppName;
    $this->Config = $Config;
    $this->rootFolder = $RootFolder;
}

protected function removeAccents($str) {
    $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
    $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
    return str_replace($a, $b, $str);
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
    $this->searchCache->putContent("[]");
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
    if (!empty($path) && substr($path, -1) !== '/' || $path !== ".")
        $path = substr($path, -1);

    $query = $input->getArgument('query');
    $query = strtolower($query);
    $query = $this->removeAccents($query);

    $this->search($path, $this->CarnetFolder->get($path), $query,0);
    $data = json_decode( $this->searchCache->getContent());
    array_push($data, "end_of_search");
    $this->searchCache->putContent(json_encode($data));
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
    $this->output->writeln('found in '.$in->getPath());
    if($this->searchCache){
        $inf = $in->getFileInfo();
        $file = array();
        $file['name'] = $inf->getName();
        $file['path'] = $relativePath."/".$inf->getName();
        $file['isDir'] = $inf->getType() === "dir";
        $file['mtime'] = $inf->getMtime();
        $data = json_decode( $this->searchCache->getContent());
        array_push($data, $file);
        $this->searchCache->putContent(json_encode($data));
    }
}
private function search($relativePath, $folder, $query, $curDepth){
    $array = array();

    foreach($folder->getDirectoryListing() as $in){
        //$this->output->writeln('in '.$in->getPath());
        
        if($in->getFileInfo()->getType() === "dir"){
            if($curDepth<30) //might be a problem in nc db
            $this->search(($relativePath!==""?relativePath."/":"").$in->getName(), $in, $query, $curDepth+1);

        }
        else{

            if(strstr($this->removeAccents(strtolower($in->getName())), $query)){
                $this->writeFound($relativePath, $in);
                continue;
            }
            try {
                $zipFile = new \PhpZip\ZipFile();
                $zipFile->openFromStream($in->fopen("r"));
                $index = $zipFile->getEntryContents("index.html");
                if(strstr($this->removeAccents(strtolower($index)), $query)){
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