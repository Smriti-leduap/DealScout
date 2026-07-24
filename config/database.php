<?php

class Database {
    private $dbh;
    private $stmt;
    private $error;

    public function __construct() {
        $dbPath = __DIR__ . '/../database.sqlite';
        $dsn = 'sqlite:' . $dbPath;

        try {
            $this->dbh = new PDO($dsn);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            
            $this->dbh->exec('PRAGMA foreign_keys = ON;');
            
            $this->dbh->sqliteCreateFunction('acos', 'acos', 1);
            $this->dbh->sqliteCreateFunction('cos', 'cos', 1);
            $this->dbh->sqliteCreateFunction('sin', 'sin', 1);
            $this->dbh->sqliteCreateFunction('radians', 'deg2rad', 1);
            
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            die("Database connection failed: " . $this->error);
        }
    }

    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    public function getDbh() {
        return $this->dbh;
    }
}
?>
