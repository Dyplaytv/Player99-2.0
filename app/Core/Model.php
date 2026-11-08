<?php
namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table = '';
    protected static string $connection = 'users';
    protected static string $primaryKey = 'id';

    public static function getConnection(): PDO
    {
        return Database::connection(static::$connection);
    }

    public static function find($id): ?array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id LIMIT 1");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function where(string $column, $value): array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM " . static::$table . " WHERE {$column} = :val");
        $stmt->bindValue(':val', $value);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function firstWhere(string $column, $value): ?array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM " . static::$table . " WHERE {$column} = :val LIMIT 1");
        $stmt->bindValue(':val', $value);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function all(): array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->query("SELECT * FROM " . static::$table);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = static::getConnection();
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})";
        $stmt = $pdo->prepare($sql);

        foreach ($data as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }

        $stmt->execute();
        return (int)$pdo->lastInsertId();
    }

    public static function updateById($id, array $data): bool
    {
        $pdo = static::getConnection();
        $fields = [];
        foreach ($data as $key => $val) {
            $fields[] = "{$key} = :{$key}";
        }
        $fieldsSql = implode(', ', $fields);

        $sql = "UPDATE " . static::$table . " SET {$fieldsSql} WHERE " . static::$primaryKey . " = :pk_id";
        $stmt = $pdo->prepare($sql);

        foreach ($data as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':pk_id', $id);

        return $stmt->execute();
    }

    public static function deleteById($id): bool
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id");
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
}
