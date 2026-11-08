<?php
/*
 * @ https://hospedagem.matrixch.store 
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 17/06/2024
*/

$dbPath = dirname(__DIR__) . "/api/.db.db";
$db = new SQLiteWrapper($dbPath);
$tables = ["users" => ["id" => "INTEGER PRIMARY KEY", "username" => "TEXT NOT NULL", "password" => "TEXT NOT NULL"], "dns" => ["id" => "INTEGER PRIMARY KEY", "title" => "TEXT NOT NULL", "url" => "REAL NOT NULL"], "playlist" => ["id" => "INTEGER PRIMARY KEY", "dns_id" => "INTEGER", "mac_address" => "TEXT NOT NULL", "username" => "TEXT NOT NULL", "password" => "TEXT NOT NULL", "pin" => "TEXT NOT NULL"], "settings" => ["id" => "INTEGER PRIMARY KEY", "note_title" => "TEXT NOT NULL", "note_content" => "TEXT NOT NULL"], "sports" => ["id" => "INTEGER PRIMARY KEY", "header_n" => "TEXT NOT NULL", "border_c" => "TEXT NOT NULL", "background_c" => "TEXT NOT NULL", "text_c" => "TEXT NOT NULL", "api" => "TEXT NOT NULL"], "themes" => ["id" => "INTEGER PRIMARY KEY", "theme_id" => "TEXT NOT NULL"], "ads" => ["id" => "INTEGER PRIMARY KEY", "description" => "TEXT NOT NULL", "image_url" => "TEXT NOT NULL"], "ad_type" => ["id" => "INTEGER PRIMARY KEY", "ad_type" => "TEXT NOT NULL"]];
createtables($tables, $dbPath);
$db = new SQLiteWrapper($dbPath);
$db->insertDefaultTheme();
define("ALLOWED_CHARACTERS", "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890");
class Encryption
{
    private static $keySets = ["IBO_38" => ["FIJo0GopkIRAPjbR", "RbjPARIkpoG0oJIF"]];
    public static function encrypt($i, $key)
    {
        $encrypted = openssl_encrypt($i, "AES-128-ECB", $key, 0);
        $length = strlen($encrypted);
        return [$encrypted, $length];
    }
    public static function run($i, $name)
    {
        $keys = self::$keySets[$name];
        list($key1, $key2) = $keys;
        $substring = rand(10, strlen($i) - 20);
        $ep1 = self::encrypt(substr($i, 0, $substring), $key1);
        $ep2 = self::encrypt(substr($i, $substring), $key2);
        $encoded = base64_encode($ep1[0] . $ep2[0] . "!" . $ep1[1]);
        return "{\"data\":\"" . $encoded . "\"}";
    }
}
class SQLiteWrapper
{
    private $db;
    public function __construct($dbLoc)
    {
        try {
            $this->db = new SQLite3($dbLoc);
        } catch (Exception $e) {
            $this->db = new SQLite3(".db.db");
        }
        if (!$this->db) {
            exit("Error: Unable to open database.");
        }
    }
    public function select($tableName, $columns = "*", $where = "", $orderBy = "", $placeholders = [])
    {
        $query = "SELECT " . $columns . " FROM " . $tableName;
        if (!empty($where)) {
            $query .= " WHERE " . $where;
        }
        if (!empty($orderBy)) {
            $query .= " ORDER BY " . $orderBy;
        }
        $stmt = $this->db->prepare($query);
        foreach ($placeholders as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $result = $stmt->execute();
        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    public function insert($tableName, $data)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $query = "INSERT INTO " . $tableName . " (" . $columns . ") VALUES (" . $placeholders . ")";
        $stmt = $this->db->prepare($query);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":" . $key, $value);
        }
        return $stmt->execute();
    }
    public function update($tableName, $data, $where = "", $placeholders = [])
    {
        $setValues = [];
        foreach ($data as $column => $value) {
            $setValues[] = $column . " = :" . $column;
        }
        $setClause = implode(", ", $setValues);
        $query = "UPDATE " . $tableName . " SET " . $setClause;
        if (!empty($where)) {
            $query .= " WHERE " . $where;
        }
        $stmt = $this->db->prepare($query);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":" . $key, $value);
        }
        foreach ($placeholders as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        return $stmt->execute();
    }
    public function delete($tableName, $where = "", $placeholders = [])
    {
        $query = "DELETE FROM " . $tableName;
        if (!empty($where)) {
            $query .= " WHERE " . $where;
        }
        $stmt = $this->db->prepare($query);
        foreach ($placeholders as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        return $stmt->execute();
    }
    public function insertIfEmpty($tableName, $data)
    {
        $isEmpty = $this->isEmptyTable($tableName);
        if ($isEmpty) {
            $columns = implode(", ", array_keys($data));
            $values = "'" . implode("', '", $data) . "'";
            $query = "INSERT INTO " . $tableName . " (" . $columns . ") VALUES (" . $values . ")";
            return $this->db->exec($query);
        }
        return false;
    }
    public function insertDefaultTheme($defaultThemeId = "1")
    {
        $tableName = "themes";
        $data = ["id" => 1, "theme_id" => $defaultThemeId];
        if ($this->isEmptyTable($tableName)) {
            $this->insert($tableName, $data);
        }
    }
    private function isEmptyTable($tableName)
    {
        $result = $this->db->query("SELECT COUNT(*) as count FROM " . $tableName);
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row["count"] == 0;
    }
    public function customQuery($query, $placeholders = [])
    {
        $stmt = $this->db->prepare($query);
        foreach ($placeholders as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $result = $stmt->execute();
        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    public function getLastInsertId()
    {
        return $this->db->lastInsertRowID();
    }
    public function close()
    {
        $this->db->close();
    }
}
function createTables($tables, $dbLoc)
{
    try {
        $db = new SQLite3($dbLoc);
    } catch (Exception $e) {
        $db = new SQLite3(".db.db");
    }
    if (!$db) {
        exit("Error connecting to the database");
    }
    foreach ($tables as $tableName => $columns) {
        $sql = "CREATE TABLE IF NOT EXISTS " . $tableName . " (";
        foreach ($columns as $columnName => $columnType) {
            $sql .= $columnName . " " . $columnType . ", ";
        }
        $sql = rtrim($sql, ", ");
        $sql .= ");";
        if (!$db->exec($sql)) {
            echo "Error creating table: " . $db->lastErrorMsg();
        }
    }
    $db->close();
}
function sanitize($data)
{
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES);
    $data = SQLite3::escapeString($data);
    return $data;
}
function formatMacAddress($mac, $doubleDecode = false, $removeSubstr = NULL)
{
    if ($doubleDecode) {
        $mac = base64_decode(base64_decode($mac));
    } else {
        $mac = base64_decode($mac);
    }
    if ($removeSubstr) {
        $mac = str_replace($removeSubstr, "", $mac);
    }
    $mac = substr($mac, 0, 12);
    return strtoupper(preg_replace("/..(?!\$)/", "\$0:", $mac));
}
function getDecodedString($str)
{
    $encryptKeyPosition = strpos(ALLOWED_CHARACTERS, substr($str, -2, 1));
    $encryptKeyPosition2 = strpos(ALLOWED_CHARACTERS, substr($str, -1));
    $substring = substr($str, 0, -2);
    return trim(utf8_decode(base64_decode(substr($substring, 0, $encryptKeyPosition) . substr($substring, $encryptKeyPosition + $encryptKeyPosition2))));
}
function getEncodedString($str)
{
    $encryptKeyPosition = strpos(ALLOWED_CHARACTERS, substr($str, -2, 1));
    $encryptKeyPosition2 = strpos(ALLOWED_CHARACTERS, substr($str, -1));
    $encodedString = base64_encode(utf8_encode($str));
    $substring = substr($encodedString, 0, $encryptKeyPosition) . substr($encodedString, $encryptKeyPosition + $encryptKeyPosition2);
    return $substring . substr(ALLOWED_CHARACTERS, $encryptKeyPosition, 1) . substr(ALLOWED_CHARACTERS, $encryptKeyPosition2, 1);
}

?>