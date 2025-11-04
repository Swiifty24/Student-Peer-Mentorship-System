<?php

class Database {

    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "tutor";

    private $connect;

    public function connect(){
        $this->connect = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
        return $this->connect;
    }

}
?>

