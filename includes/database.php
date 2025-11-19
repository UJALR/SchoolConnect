<?php
// database.php
// Contains the Database class

// Reference: https://phpdelusions.net/pdo/pdo_wrapper 
// Switched from mysqli to PDO
class Database
{
    public $pdo;
    private static ?Database $instance = null;

    public function __construct()
    {
        $db = parse_ini_file('database.ini');

        $dbHost = $db['host'];
        $dbName = $db['dbname'];
        $dbUser = $db['username'];
        $dbPass = $db['password'];
        $dbPort = $db['port'];
        $dbCharset = $db['charset'];

        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $dsn = "mysql:host=$dbHost; dbname=$dbName; port=$dbPort; charset=$dbCharset";

        try
        {
            $this->pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        }
        catch (PDOException $e)
        {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    // Get the instance of the Database. If there's none, create one.
    public static function getInstance(): ?Database
    {
        if (self::$instance === null)
        {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    // Get the database connection.
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // Simplified prepare execute
    public function run($sql, $args = NULL)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}