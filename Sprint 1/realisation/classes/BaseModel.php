<?php
/**
 * Parent class of every model (Player, Game, ...).
 * It only gives them the shared PDO connection.
 */
abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** True when the exception is a MySQL "duplicate entry" error. */
    protected function isDuplicate(PDOException $e): bool
    {
        return ($e->errorInfo[1] ?? 0) === 1062;
    }
}
