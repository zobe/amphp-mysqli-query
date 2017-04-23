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
     * @var mixed|null
     */
    protected $result = null;

    /**
     * @var mixed the result of \mysqli_reap_async_query itself
     */
    protected $resultRaw = null;

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
     * if you use Query::query(),
     * this value is \mysqli_query or null.
     * and you should free the \mysqli_query object.
     *
     * if you use Query::execOnly(),
     * this value is always null.
     * in either case there is no need to free any object.
     *
     * if you use Query::getFirstRowOnly(),
     * this value is an array or null.
     * in either case there is no need to free any object.
     *
     * if you use Query::getFirstValueOnly(),
     * this value is a mixed value or null.
     * in either case there is no need to free any object.
     *
     * this value is based on mysqli_reap_async_query function,
     * but when the function returns a value other than \mysqli_result,
     * this function always acts to return null.
     * if you need mysqli_reap_async_query's result itself,
     * see Result::getResultRaw()
     *
     * Do not forget mysqli_free_results() or $this->freeResult()
     *
     * if you call Query::query(,,true) or Query::execOnly(), this value is always null.
     *
     * @see Result::getResultRaw()
     * @see Result::freeResult()
     * @return mixed|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed|null $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

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

    /**
     * call mysqli_free_result for internal mysqli_result if exists
     */
    public function freeResult()
    {
        if( !is_null($this->result) && $this->result instanceof \mysqli_result ) {
            mysqli_free_result($this->result);
            $this->result = null;
        }
    }
}
