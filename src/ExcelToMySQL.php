<?php
namespace Frantzley;

use Exception;
use PDO;

class ExcelToMySQL
{
    protected PDO $pdo;
    protected string $filePath;
    protected ?string $tableName = null;
    protected ?string $uniqueKey = null;
    protected array $mapping     = [];
    protected array $logs        = [];
    protected array $summary     = [
        'inserted' => 0,
        'exists'   => 0,
        'error'    => 0,
    ];

    public function __construct(string $filePath, PDO $pdo)
    {
        $this->filePath = $filePath;
        $this->pdo      = $pdo;
    }

    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;
    }

    public function setUniqueKey(string $uniqueKey)
    {
        $this->uniqueKey = $uniqueKey;
    }

    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function createTableIfNotExists(array $headers): void
    {
        if (! $this->tableName) {
            throw new Exception("Ou dwe mete non tab la avan kreye li.");
        }

        $table = $this->tableName;

        // prepare kolon yo
        $columnsSQL = [];
        foreach ($headers as $col) {
            $col = trim($col);
            if ($col !== '') {
                $columnsSQL[] = "`$col` VARCHAR(255)";
            }
        }

        $uniqueSQL = $this->uniqueKey ? ", UNIQUE(`{$this->uniqueKey}`)" : "";

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        " . implode(", ", $columnsSQL) . "
        $uniqueSQL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->pdo->exec($sql);
    }

    /**
     * Auto-map kolòn Excel yo → kolòn MySQL
     * Si $createTable = true, li kreye tab la otomatikman si li pa egziste
     */
    public function autoMap(array $headers, bool $createTable = true): void
    {
        $mapping = [];

        foreach ($headers as $header) {
            if ($header) {
                // konvèti header an safe SQL column name
                $col              = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $header), '_'));
                $mapping[$header] = $col;
            }
        }

        $this->mapping = $mapping;

        if ($createTable && $this->tableName) {
            $columnsSQL = [];
            foreach ($mapping as $excelCol => $dbCol) {
                $columnsSQL[] = "`$dbCol` VARCHAR(255)";
            }

            $uniqueSQL = $this->uniqueKey ? ", UNIQUE(`{$this->uniqueKey}`)" : "";
            $sql       = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        " . implode(',', $columnsSQL) .
                $uniqueSQL . "
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            $this->pdo->exec($sql);
            $this->log("Tab `{$this->tableName}` kreye oswa deja egziste.", "info");
        }
    }

    public function insertOrUpdateRow(array $data): string
    {
        if (! $this->tableName) {
            throw new Exception("Non tab la pa defini");
        }

        $table = $this->tableName;

        // Verifye si done a deja egziste si gen uniqueKey
        if ($this->uniqueKey) {
            $parts     = explode('.', $this->uniqueKey);
            $uniqueCol = $parts[1] ?? $parts[0];

            $possibleKeys = [$this->uniqueKey, $uniqueCol];
            $uniqueValue  = null;

            foreach ($possibleKeys as $key) {
                if (isset($data[$key])) {
                    $uniqueValue = $data[$key];
                    break;
                }
            }

            if ($uniqueValue !== null) {
                $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$uniqueCol` = ?");
                $stmtCheck->execute([$uniqueValue]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $this->log("Done deja egziste: " . json_encode($data), "info");
                    $this->summary['exists']++;
                    return 'exists';
                }
            }
        }

        // Insert done si li pa egziste
        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql          = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt         = $this->pdo->prepare($sql);

        try {
            $stmt->execute(array_values($data));
            $this->log("Done inserte: " . json_encode($data), "insert");
            $this->summary['inserted']++;
            return 'insert';
        } catch (\Exception $e) {
            $this->summary['error']++;
            //$this->log("Echèk SQL: " . $e->getMessage(), "error");
            return 'error';
        }
    }

    protected function log(string $message, string $type = 'INFO')
    {
        $this->logs[] = ['log' => $message, 'type' => strtolower($type)];
        ///error_log("[$type] $message"); // log nan terminal
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getSummary(): array
    {
        return $this->summary;
    }
}
