<?php
namespace YourNamespace\ExcelToMySQL;

use PDO;

class TableManager {
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName){
        $this->pdo=$pdo;
        $this->tableName=$tableName;
    }

    public function createTableIfNotExists(array $columns): void {
        $cols=[];
        foreach($columns as $col=>$type){
            $cols[]="`$col` $type";
        }
        $colsSql=implode(", ",$cols);
        $sql="CREATE TABLE IF NOT EXISTS `{$this->tableName}` (id INT AUTO_INCREMENT PRIMARY KEY, $colsSql) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->pdo->exec($sql);
    }
}
?>