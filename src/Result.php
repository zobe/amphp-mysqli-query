<?php

namespace zobe\AmphpMysqliQuery;

/**
 * Hold the result of the query
 *
 * @package zobe\AmphpMysqliQuery
 */
class Result
{
    protected $sql;

    /**
     * @var \mysqli_result|null
     */
    protected $result = null;

    /**
     * @var mixed the result of \mysqli_reap_async_query itself
     */
    protected $resultRaw = null;

//    /**
//     * @var \Throwable|null
//     */
//    protected $error = null;

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
     * as the result of mysqli_reap_async_query,
     * this function returns mysqli_result or null.
     *
     * ex1. 'select value from table' => mysqli_result
     * ex2. 'insert into table values (value1, value2)' => null
     *
     * if you need mysqli_reap_async_query's result itself,
     * see Result::getResultRaw()
     *
     * Do not forget mysqli_free_results()
     *
     * @see Result::getResultRaw()
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

//    public function isError() : bool
//    {
//        return is_null($this->error) ? false : true;
//    }

//    public function getError()
//    {
//        return $this->error;
//    }
//
//    public function setError( \Throwable $e )
//    {
//        $this->error = $e;
//    }

    /**
     * return value of mysqli_reap_async_query AS IS.
     *
     * @return mixed
     */
    public function getResultRaw()
    {
        return $this->resultRaw;
    }

    /**
     * @param $resultRaw mixed
     */
    public function setResultRaw($resultRaw)
    {
        $this->resultRaw = $resultRaw;
    }
}
