<?php
namespace Frantzley;

use PDO;
use PDOException;

class ExcelToMySQL
{
    protected string $filePath;
    protected PDO $pdo;
    protected array $mapping     = [];
    protected ?string $uniqueKey = null;
    protected int $inserted      = 0;
    protected int $updated       = 0;
    protected string $logFile;
    protected ?string $tableName = null;

    public function __construct(string $filePath, PDO $pdo, ?string $logFile = null)
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("Fichier Excel la pa jwenn: $filePath");
        }
        $this->filePath = $filePath;
        $this->pdo      = $pdo;
        $this->logFile  = $logFile ?? __DIR__ . '/import_errors.log';
    }

    public function setMapping(array $mapping): void
    {
        if (empty($mapping)) {
            throw new \InvalidArgumentException("Ou dwe defini omwen yon mapping pou enpòte done yo.");
        }
        // retire kolòn vid nan mapping
        $this->mapping = array_filter($mapping, fn($col) => trim($col) !== '');
    }

    public function setUniqueKey(string $uniqueKey): void
    {
        $this->uniqueKey = $uniqueKey;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function insertOrUpdateRow(array $data): void
    {
        $table = $this->tableName ?? 'sheet';

        // retire kolòn vid nan done
        $data = array_filter($data, fn($val, $col) => trim($col) !== '', ARRAY_FILTER_USE_BOTH);
        if (empty($data)) {
            return;
        }

        // Kreye tab la yon sèl fwa si li pa egziste
        static $tableCreated = [];
        if (! isset($tableCreated[$table])) {
            $columnsSQL = [];
            foreach ($data as $col => $val) {
                $columnsSQL[] = "`$col` VARCHAR(255)";
            }

            $uniqueSQL = $this->uniqueKey ? ", UNIQUE(`$this->uniqueKey`)" : "";

            $createTableSQL = "CREATE TABLE IF NOT EXISTS `$table` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                " . implode(',', $columnsSQL) . "
                $uniqueSQL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $this->pdo->exec($createTableSQL);

            $tableCreated[$table] = true;
        }

        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $values       = array_values($data);

        try {
            if ($this->uniqueKey) {
                $updateFields = [];
                foreach ($columns as $col) {
                    if ($col !== $this->uniqueKey) {
                        $updateFields[] = "`$col` = VALUES(`$col`)";
                    }
                }

                $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`)
                        VALUES (" . implode(',', $placeholders) . ")
                        ON DUPLICATE KEY UPDATE " . implode(',', $updateFields);
            } else {
                $sql = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`)
                        VALUES (" . implode(',', $placeholders) . ")";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);

            if ($this->uniqueKey && $stmt->rowCount() === 2) {
                $this->updated++;
            } else {
                $this->inserted++;
            }

        } catch (PDOException $e) {
            throw new \RuntimeException("Echèk SQL pandan enpòtasyon: " . $e->getMessage());
        }
    }

    public function getSummary(): array
    {
        return [
            'inserted' => $this->inserted,
            'updated'  => $this->updated,
        ];
    }
}
