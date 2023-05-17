<?php

namespace App\Controllers;

use DevCoder\DotEnv;
use PDO;

class DatabaseController
{
    /**
     * @var PDO
     */
    private PDO $connection;
    public function __construct()
    {
        $absolutePathToEnvFile = __DIR__ . '/../../.env';
        (new DotEnv($absolutePathToEnvFile))->load();

        $servername = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $databasename = getenv('DB_DATABASE');
        $username = getenv('DB_USERNAME');
        $password = getenv('DB_PASSWORD');

        $this->connection = new PDO("mysql:host=$servername:$port;dbname=$databasename;charset=utf8", $username, $password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->query("SET wait_timeout=28800;");
    }

    public function getConnection()
    {
        return $this->connection;
    }
}