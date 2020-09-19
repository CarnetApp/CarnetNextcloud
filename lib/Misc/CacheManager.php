<?php
namespace OCA\Carnet\Misc;
use OCP\IDBConnection;
use OCA\Carnet\Misc\NoteUtils;

class CacheManager{
    private $db;
    private $carnetFolder;
    public function __construct(IDBConnection $db, $carnetFolder) {
        $this->db = $db;
        $this->carnetFolder = $carnetFolder;
    }

    public function addToCache($relativePath, $metadata, $lastmodfile, $text){
        $this->addToCacheFullPath($this->carnetFolder->getFullPath($relativePath), $metadata, $lastmodfile, $text);
    }

    public function addToCacheFullPath($fullPath, $metadata, $lastmodfile, $text){
        if(isset($metadata['text']))
            unset($metadata['text']);
        $low_case_text = strtolower(NoteUtils::removeAccents($text));
        $sql = 'INSERT into `*PREFIX*carnet_metadata` VALUES(?, ?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $fullPath, \PDO::PARAM_STR);
        if(!is_string($metadata))
            $metadata = json_encode($metadata);
        $stmt->bindParam(2, $metadata, \PDO::PARAM_STR);
        $stmt->bindParam(3, $lastmodfile, \PDO::PARAM_INT);
        $stmt->bindParam(4, $low_case_text, \PDO::PARAM_STR);

        try{
            $stmt->execute();
        }
        catch(\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
            $sql = 'UPDATE `*PREFIX*carnet_metadata` SET `metadata` = ?, `last_modification_file` = ?, `low_case_text` = ? WHERE `path` = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(1, $metadata, \PDO::PARAM_STR);
            $stmt->bindParam(2, $lastmodfile, \PDO::PARAM_INT);
            $stmt->bindParam(3, $low_case_text, \PDO::PARAM_STR);
            $stmt->bindParam(4, $fullPath, \PDO::PARAM_STR);

            $stmt->execute();
        }

    }

    public function buildCache($config, $appName, $rootFolder, $users){
        $arrayFolder = array();
        $sql = 'SELECT path, last_modification_file FROM `*PREFIX*carnet_metadata`';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $cache = array();
        $fetched = $stmt->fetchAll();
        foreach ($fetched as $row){
            $cache[$row['path']] = $row['last_modification_file'];
        }

        $stmt->closeCursor();
        foreach($users as $user){
            $notePath = $config->getUserValue($user, $appName, "note_folder");
            if(empty($notePath))
                $notePath= NoteUtils::$defaultCarnetNotePath;
            try {
                echo $notePath."pet";
                $carnetFolder = $rootFolder->getUserFolder($user)->get($notePath);
                $this->recursiveAddToCache($carnetFolder, $carnetFolder, $cache);
            } catch(\OCP\Files\NotFoundException $e) {
               
            }

        }
    }

    private function recursiveAddToCache($carnetFolder, $node, $cache){
        if($node instanceof \OCP\Files\Folder){
            foreach($node->getDirectoryListing() as $inNode){
                echo $inNode->getPath()."\n";
                $this->recursiveAddToCache($carnetFolder, $inNode, $cache);
            }
        } else if(substr($node->getName(), -3) === "sqd"){
            $inf = $node->getFileInfo();
            if($cache[$node->getPath()] != null && $inf->getMtime() == $cache[$node->getPath()]){
                return;
            }
            $utils = new NoteUtils();
            try{
                $relativePath = substr($node->getPath(), strlen($carnetFolder->getPath()));
                if(substr($relativePath, 0, 1) === "/")
                    $relativePath = substr($relativePath, 1);
                $meta = $utils->getMetadata($carnetFolder, $relativePath);
                $text = $meta['text'];
                $this->addToCacheFullPath($node->getPath(), $meta, $meta['lastmodfile'], $text);
            } catch(\PhpZip\Exception\ZipException $e){

            } catch(\OCP\Encryption\Exceptions\GenericEncryptionException $e){
                
            }
        }

    }

    public function deleteFromCache($relativePath){
        $sql = 'DELETE FROM `*PREFIX*carnet_metadata` WHERE `path` = ?';
        $stmt = $this->db->prepare($sql);
        $param = $this->carnetFolder->getFullPath($relativePath);
        $stmt->bindParam(1, $param, \PDO::PARAM_STR);
        $stmt->execute();

    }
    public function getFromCache($arrayRelativePath){
        return $this->getFromCache2($arrayRelativePath, false);
    }

    public function getFromCache2($arrayRelativePath, $withLowCaseText){
        $total = 0;
        $array = array();

        while($total < sizeof($arrayRelativePath)){
            $arrayFullPath = array();
            $sql = 'SELECT * FROM `*PREFIX*carnet_metadata` ' . 
            'WHERE ';
            for($i = $total; $i < sizeof($arrayRelativePath); $i++){
                $sql .= "`path` = ? ";
                
                array_push($arrayFullPath, $this->carnetFolder->getFullPath($arrayRelativePath[$i]));
                if($i < sizeof($arrayRelativePath)-1 && $i - $total < 100)
                    $sql .= "OR ";
                if($i - $total == 100)
                    break;
            }
            $total = $total + 100;
        
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute($arrayFullPath);
            $fetched = $stmt->fetchAll();
            foreach ($fetched as $row){
                $metadata = json_decode($row['metadata'], true);
                $item = array();
                if(!$withLowCaseText){
                    $item = $metadata;
                } else {
                    $item['metadata'] = $metadata;
                    $item['low_case_text'] = $row['low_case_text'];
                }
                $array[substr($row['path'], strlen($this->carnetFolder->getPath())+1)] = $item;
            }

            $stmt->closeCursor();
        }
        return $array;
    }

    public function search($query){
        $arrayFullPath = array();
        $sql = 'SELECT * FROM `*PREFIX*carnet_metadata` ' . 
        'WHERE path LIKE ? AND (metadata LIKE ? OR low_case_text LIKE ?)';
        $args = array();
        array_push($args, $this->carnetFolder->getPath()."/%");
        array_push($args, "%".$query."%");
        array_push($args, "%".$query."%");

        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        $array = array();
        $fetched = $stmt->fetchAll();
        foreach ($fetched as $row){
            $array[substr($row['path'], strlen($this->carnetFolder->getPath())+1)] = json_decode($row['last_modification_file']);
        }

        $stmt->closeCursor();
        return $array;
    }
}
?>
