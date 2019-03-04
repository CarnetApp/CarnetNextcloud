<?php
namespace OCA\Carnet\Misc;
use OCP\IDBConnection;

class CacheManager{
    private $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    public function addToCache($relativePath, $metadata, $lastmodfile){
        $sql = 'INSERT into `*PREFIX*carnet_metadata` VALUES(?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $relativePath, \PDO::PARAM_STR);
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
            $stmt->bindParam(3, $relativePath, \PDO::PARAM_STR);
            $stmt->bindParam(2, $lastmodfile, \PDO::PARAM_INT);

            $stmt->execute();
        }

    }

    public function getFromCache($arrayRelativePath){
        $sql = 'SELECT * FROM `*PREFIX*carnet_metadata` ' .
        'WHERE ';
        for($i = 0; $i < sizeof($arrayRelativePath); $i++){
            $sql .= "`path` = ? ";
            if($i < sizeof($arrayRelativePath)-1)
                $sql .= "OR ";
        }
       
        $stmt = $this->db->prepare($sql);
       /* foreach($arrayRelativePath as $relativePath){
            $stmt->bindParam($i+1, $relativePath, \PDO::PARAM_STR);
            $i++;
        }*/
        
        $stmt->execute($arrayRelativePath);
        $array = array();
        $fetched = $stmt->fetchAll();
        foreach ($fetched as $row){
            $array[$row['path']] = json_decode($row['metadata']);
        }

        $stmt->closeCursor();
        return $array;
    }
}
?>