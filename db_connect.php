<?php

// Connecting to the Database
$servername = "localhost";
$username = "root";
$password = "";
$database = "mydb";

// Create a connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Die if connection was not successful
if (!$conn) {
    die("Sorry we failed to connect: " . mysqli_connect_error());
}

class DatabaseConnection
{
    // Connecting to the Database
    protected $servername = "localhost";
    protected $username = "root";
    protected $password = "";
    protected $database = "mydb";

    /**
     * Create a connection
     */
    function db_connect()
    {
        $this->_db_connect = mysqli_connect($this->servername, $this->username, $this->password, $this->database);

        // Die if connection was not successful
        if (!$this->_db_connect) {
            die("Sorry we failed to connect: " . mysqli_connect_error());
        }
        return $this->_db_connect;
    }
}