<?php

namespace zobe\AmphpMysqliQuery;

/**
 * A QueryType is used to specify how the query treat the result.
 *
 * @package zobe\AmphpMysqliQuery
 */
class QueryType
{
    protected $type = 0;
    protected static $singletons = [];

    protected static $cNormal = 0;
    protected static $cExecOnly = 1;
    protected static $cFirstRowOnly = 2;
    protected static $cFirstValueOnly = 3;

    /**
     * Use typeXX static methods to get.
     *
     * @param int $type
     */
    protected function __construct( int $type )
    {
        $this->type = $type;
    }

    protected static function typeGet( int $a ) : QueryType
    {
        if( !array_key_exists( $a, self::$singletons ) )
        {
            self::$singletons[$a] = new QueryType( $a );
        }
        return self::$singletons[$a];
    }

    /**
     * Specifies the query must return \mysqli_result or null
     *
     * @return QueryType
     */
    public static function typeNormal() : QueryType
    {
        return self::typeGet(self::$cNormal);
    }

    /**
     * Specifies the query must silently execute and has no return values.
     *
     * @return QueryType
     */
    public static function typeExecOnly() : QueryType
    {
        return self::typeGet(self::$cExecOnly);
    }

    /**
     * Specifies the query must return an array of the first row or null, and
     * the \mysqli_result must be disposed silently.
     *
     * @return QueryType
     */
    public static function typeFirstRowOnly() : QueryType
    {
        return self::typeGet(self::$cFirstRowOnly);
    }

    /**
     * Specifies the query must return the first value of the first row or null, and
     * the \mysqli_result must be disposed silently.
     *
     * @return QueryType
     */
    public static function typeFirstValueOnly() : QueryType
    {
        return self::typeGet(self::$cFirstValueOnly);
    }


    public function isNormal() : bool
    {
        return ($this->type === self::$cNormal) ? true : false;
    }

    public function isExecOnly() : bool
    {
        return ($this->type === self::$cExecOnly) ? true : false;
    }

    public function isFirstRowOnly() : bool
    {
        return ($this->type === self::$cFirstRowOnly) ? true : false;
    }

    public function isFirstValueOnly() : bool
    {
        return ($this->type === self::$cFirstValueOnly) ? true : false;
    }
}

