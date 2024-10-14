<?php

namespace Database;

use mysqli;

class Database
{
    private $servername = "127.0.0.1:3308";
    private $username = "root";
    private $password = "";
    private $dbname = "csustore";
    public $conn;

    public function __construct()
    {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function close()
    {
        $this->conn->close();
    }
}
