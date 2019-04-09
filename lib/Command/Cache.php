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

class Cache extends Command {


        /**
         * @param string $appName
         * @param IRootFolder $rootFolder
    */
    public function __construct($AppName, $RootFolder,  $Config,  IDBConnection $IDBConnection, $UserManager){
        parent::__construct();
        $this->appName = $AppName;
        $this->Config = $Config;
        $this->rootFolder = $RootFolder;
        $this->db = $IDBConnection;
        $this->userManager = $UserManager;
        
    }
    /**
    * @param InputInterface $input
    * @param OutputInterface $output
    * @return int
    */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $this->action = $input->getArgument('action');
        if($this->action === "rebuild"){
            $users = $this->userManager->search("",20000, 0);
            $arrayId = array();
            echo count($users);
            foreach($users as $user){
                array_push($arrayId,$user->getUID());
            }
            $cache = new CacheManager($this->db, null);
            $cache->buildCache($this->Config, $this->appName, $this->rootFolder, $arrayId);
        }
    }
    protected function configure() {
        $this->setName('carnet:cache')
        ->setDescription('Cache')
        ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Action: rebuild, clear'
        ); 
    }
}

?>