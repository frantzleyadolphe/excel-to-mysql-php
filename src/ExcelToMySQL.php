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

    public function insertOrUpdateRow(array $data): string
    {
        if (! $this->tableName) {
            throw new Exception("Non tab la pa defini");
        }

        $table = $this->tableName;

        // Tcheke kle inik
        if ($this->uniqueKey && isset($data[$this->uniqueKey])) {
            $parts     = explode('.', $this->uniqueKey);
            $uniqueCol = $parts[1] ?? $parts[0];

            $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$uniqueCol` = ?");
            $stmtCheck->execute([$data[$this->uniqueKey]]);
            if ($stmtCheck->fetchColumn() > 0) {
                $this->log("Done deja egziste: " . json_encode($data), "INFO");
                $this->summary['error']++;
                return 'error';
            }
        }

        // Insert done si pa deja egziste
        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql          = "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt         = $this->pdo->prepare($sql);

        try {
            $stmt->execute(array_values($data));
            $this->log("Done inserte: " . json_encode($data), "INSERT");
            $this->summary['inserted']++;
            return 'insert';
        } catch (\Exception $e) {
            $this->log("Echèk SQL pandan enpòtasyon: " . $e->getMessage(), "ERROR");
            return 'error';
        }
    }

    protected function log(string $message, string $type = 'INFO')
    {
        $this->logs[] = ['log' => $message, 'type' => strtolower($type)];
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
