<?php

namespace zobe\AmphpMysqliQuery;

use Amp;

/**
 * Do not use.
 *
 * Query class uses.
 *
 */
class QueryInfo
{
    protected $defer = null;
    protected $sql;
    protected $connection = null;
    protected $queryType = null;

    function __construct()
    {
        $this->queryType = QueryType::typeNormal();
    }

    public function getDefer() : Amp\Deferred
    {
        if( is_null($this->defer) )
            $this->defer = new \Amp\Deferred();
        return $this->defer;
    }

    /**
     * @return string
     */
    public function getSql() : string
    {
        return $this->sql;
    }

    /**
     * @param string $sql
     */
    public function setSql(string $sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return \mysqli
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param \mysqli $connection
     */
    public function setConnection( \mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return QueryType
     */
    public function getQueryType()
    {
        return $this->queryType;
    }

    /**
     * @param QueryType $queryType
     */
    public function setQueryType(QueryType $queryType)
    {
        $this->queryType = $queryType;
    }
}

