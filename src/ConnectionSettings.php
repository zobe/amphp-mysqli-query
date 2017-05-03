<?php

/**
 * This represents the arguments of mysqli_connect() and mysqli_real_connect()
 */
class ConnectionSettings
{
    protected $host = NULL;
    protected $user = NULL;
    protected $password = NULL;
    protected $database = NULL;
    protected $port = 0;
    protected $socket = NULL;

    /**
     * ConnectionSettings constructor.
     *
     * All parameters are same as mysqli_connect() and mysqli_real_connect(). See their documents.
     *
     * @see http://php.net/manual/en/mysqli.construct.php
     * @see http://php.net/manual/en/mysqli.real-connect.php
     *
     * @param string|NULL $host
     * @param string|NULL $user
     * @param string|NULL $password
     * @param string|NULL $database
     * @param int $port
     * @param string|NULL $socket
     */
    function __construct( string $host = NULL, string $user = NULL, string $password = NULL, string $database = NULL,
                          int $port = 0, string $socket = NULL )
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->socket = $socket;
    }

    /**
     * @return null|string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return null|string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return null|string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return null|string
     */
    public function getSocket()
    {
        return $this->socket;
    }
}

