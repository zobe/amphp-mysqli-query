<?php

namespace zobe\AmphpMysqliQuery;

class Result
{
    protected $sql;

    /**
     * @var \mysqli_result|null
     */
    protected $result = null;

    /**
     * @var \Throwable|null
     */
    protected $error = null;

    /**
     * @return string executed SQL statement
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param string $sql
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
    }

    /**
     * Do not forget mysqli_free_results()
     *
     * @return \mysqli_result|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param \mysqli_result|null $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    public function isError() : bool
    {
        return is_null($this->error) ? false : true;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setError( \Throwable $e )
    {
        $this->error = $e;
    }
}
