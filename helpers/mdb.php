<?php
namespace framework\helpers;

class mdb
{

    /** @var \MongoDB\Client */
    public $client;

    /** @var \MongoDB\Database */
    public $db;

    public function __construct()
    {

        $mongodbServer = $_SESSION[AS_APP]['environment']['mongodb']['server'];
        $mongoDatabaseName = $_SESSION[AS_APP]['environment']['mongodb']['database'];

        $mongoParams = [];
        if(isset($_SESSION[AS_APP]['environment']['mongodb']['auth'])) {
            $mongoParams = [
                'username' => $_SESSION[AS_APP]['environment']['mongodb']['auth']['username'],
                'password' => $_SESSION[AS_APP]['environment']['mongodb']['auth']['password'],
                'authSource' => $_SESSION[AS_APP]['environment']['mongodb']['auth']['authSource']
            ];
        }

        $this->client = new \MongoDB\Client('mongodb://' . $mongodbServer, $mongoParams);

        $this->db = $this->client->$mongoDatabaseName;

    }
}