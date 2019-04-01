<?php
namespace OCA\Carnet\Misc;
use OCP\IDBConnection;

class CacheManager{
    private $db;
    private $carnetFolder;
    public function __construct(IDBConnection $db, $carnetFolder) {
        $this->db = $db;
        $this->carnetFolder = $carnetFolder;
    }

    public function addToCache($relativePath, $metadata, $lastmodfile){
        $sql = 'INSERT into `*PREFIX*carnet_metadata` VALUES(?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $this->carnetFolder->getFullPath($relativePath), \PDO::PARAM_STR);
        if(!is_string($metadata))
            $metadata = json_encode($metadata);
        $stmt->bindParam(2, $metadata, \PDO::PARAM_STR);
        $stmt->bindParam(3, $lastmodfile, \PDO::PARAM_INT);
        try{
            $stmt->execute();
        }
        catch(\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
            $sql = 'UPDATE `*PREFIX*carnet_metadata` SET `metadata` = ?, `last_modification_file` = ? WHERE `path` = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(1, $metadata, \PDO::PARAM_STR);
            $stmt->bindParam(3, $this->carnetFolder->getFullPath($relativePath), \PDO::PARAM_STR);
            $stmt->bindParam(2, $lastmodfile, \PDO::PARAM_INT);

            $stmt->execute();
        }

    }

    public function deleteFromCache($relativePath){
        $sql = 'DELETE FROM `*PREFIX*carnet_metadata` WHERE `path` = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $this->carnetFolder->getFullPath($relativePath), \PDO::PARAM_STR);
        $stmt->execute();

    }

    public function getFromCache($arrayRelativePath){
        $arrayFullPath = array();
        $sql = 'SELECT * FROM `*PREFIX*carnet_metadata` ' . 
        'WHERE ';
        for($i = 0; $i < sizeof($arrayRelativePath); $i++){
            $sql .= "`path` = ? ";
            if($i < sizeof($arrayRelativePath)-1)
                $sql .= "OR ";
            array_push($arrayFullPath, $this->carnetFolder->getFullPath($arrayRelativePath[$i]));
        }
       
        $stmt = $this->db->prepare($sql);
       /* foreach($arrayRelativePath as $relativePath){
            $stmt->bindParam($i+1, $relativePath, \PDO::PARAM_STR);
            $i++;
        }*/
        
        $stmt->execute($arrayFullPath);
        $array = array();
        $fetched = $stmt->fetchAll();
        foreach ($fetched as $row){
            $array[substr($row['path'], strlen($this->carnetFolder->getPath())+1)] = json_decode($row['metadata']);
        }

        $stmt->closeCursor();
        return $array;
    }
}
?>