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
            //$this->log("Echèk SQL pandan enpòtasyon: " . $e->getMessage(), "error");
            $this->summary['error']++;
            return 'error';
        }
    }

    protected function log(string $message, string $type = 'INFO')
    {
        $this->logs[] = ['log' => $message, 'type' => strtolower($type)];
        error_log($message); // log nan terminal
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
