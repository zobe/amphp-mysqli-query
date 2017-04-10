<?php

namespace zobe\AmphpMysqliQuery;


trait SqlStatementHoldExceptionTrait
{
    protected $associatedSql_SqlStatementHoldExceptionTrait = '';

    /**
     * @return string
     */
    public function getSql(): string
    {
        return $this->associatedSql_SqlStatementHoldExceptionTrait;
    }

    /**
     * @param string $sql
     */
    public function setSql(string $sql )
    {
        $this->associatedSql_SqlStatementHoldExceptionTrait = $sql;
    }
}

class MysqliException extends \Exception
{
    use SqlStatementHoldExceptionTrait;

    public static $defaultMessage = 'MySQLi Error';
    public function __construct($message = null,
                                $code = 0, \Exception $previous = null) {
        if( is_null($message) )
            $message = self::$defaultMessage;
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class NotCategorizedMysqliPollException extends MysqliException
{
    // use SqlStatementHoldExceptionTrait;

    public static $defaultMessage = 'Not Categorized Exception on mysqli_poll function';
    public function __construct($message = null,
                                $code = 0, \Exception $previous = null) {
        if( is_null($message) )
            $message = self::$defaultMessage;
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class CouldntFetchMysqliException extends MysqliException
{
    // use SqlStatementHoldExceptionTrait;

    public static $defaultMessage = 'Couldn' . "'" . 't fetch mysqli Exception on mysqli_poll function';
    public function __construct($message = null,
                                $code = 0, \Exception $previous = null) {
        if( is_null($message) )
            $message = self::$defaultMessage;
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}


