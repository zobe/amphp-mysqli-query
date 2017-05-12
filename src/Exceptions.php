<?php

namespace zobe\AmphpMysqliQuery;

interface SqlStatementHoldExceptionInterface
{
    public function getSql() : string;
    public function setSql(string $sql);
}

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

interface MysqliExceptionTypeInterface
{
    public function isConnectionError(): bool;
    public function setConnectionError(bool $connectionError);
    public function isLockError(): bool;
    public function setLockError(bool $lockError);
    public function setMysqliExceptionType( MysqliExceptionTypeInterface $typeIf );
    public function getClassName(): string;
    public function setClassName(string $className);
    public function getMethodName(): string;
    public function setMethodName(string $methodName);
}

trait MysqliExceptionTypeTrait
{
    protected $connectionError_mysqliExceptionTypeTrait = false;
    protected $lockError_mysqliExceptionTypeTrait = false;
    protected $className_mysqliExceptionTypeTrait = '';
    protected $methodName_mysqliExceptionTypeTrait = '';

    /**
     * The connection may be broken, in order to execute next query, you must reestablish a connection to the MySQL server.
     *
     * @return bool
     */
    public function isConnectionError(): bool
    {
        return $this->connectionError_mysqliExceptionTypeTrait;
    }

    /**
     * do not use
     * @param bool $connectionError
     */
    public function setConnectionError(bool $connectionError)
    {
        $this->connectionError_mysqliExceptionTypeTrait = $connectionError;
    }

    /**
     * The query was involved in 'lock' problem. You might have to rollback and restart current transaction.
     *
     * Note: On transaction isolation level SERIALIZABLE, You should rollback and restart current transaction.
     * On another transaction isolation level, You should have some sleep time and restart current query.
     *
     * Note2: On the overload of mysql, you may have to simply disconnect and take a long sleep until retry.
     *
     * @return bool
     */
    public function isLockError(): bool
    {
        return $this->lockError_mysqliExceptionTypeTrait;
    }

    /**
     * do not use
     * @param bool $lockError
     */
    public function setLockError(bool $lockError)
    {
        $this->lockError_mysqliExceptionTypeTrait = $lockError;
    }

    public function setMysqliExceptionType( MysqliExceptionTypeInterface $typeIf )
    {
        $this->setLockError( $typeIf->isLockError() );
        $this->setConnectionError( $typeIf->isConnectionError() );
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className_mysqliExceptionTypeTrait;
    }

    /**
     * @param string $className
     */
    public function setClassName(string $className)
    {
        $this->className_mysqliExceptionTypeTrait = $className;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName_mysqliExceptionTypeTrait;
    }

    /**
     * @param string $methodName
     */
    public function setMethodName(string $methodName)
    {
        $this->methodName_mysqliExceptionTypeTrait = $methodName;
    }
}

class MysqliException extends \Exception
{
    use SqlStatementHoldExceptionTrait;
    use MysqliExceptionTypeTrait;

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

class MysqliExceptionType implements MysqliExceptionTypeInterface
{
    use MysqliExceptionTypeTrait;

//    public function createConnectionErrorType()
//    {
//        $a = new MysqliExceptionType();
////        $a->setConnectionError( true );
//    }
}

class MysqliExceptionClassifier
{
    protected static $lookupTable = null;

    public static function addClassificationPair( string $methodName, int $errCode, MysqliExceptionType $type )
    {
        self::staticallyInitialize();

        self::$lookupTable[self::createClassificationKey($methodName,$errCode)] = $type;
    }

    public static function removeClassificationPair( string $methodName, int $errCode )
    {
        self::staticallyInitialize();

        unset( self::$lookupTable[self::createClassificationKey($methodName,$errCode)] );
    }

    public static function createClassificationKey( string $methodName, int $errCode )
    {
        self::staticallyInitialize();

        return $methodName . ':' . $errCode;
    }

    public static function getClassificationTable()
    {
        self::staticallyInitialize();

        return self::$lookupTable;
    }

    public static function createMysqliExceptionType( string $methodName, int $errCode ) : MysqliExceptionType
    {
        $key = self::createClassificationKey($methodName,$errCode);
        if( array_key_exists($key,self::$lookupTable) )
        {
            $ret = (self::$lookupTable[$key]);
        }
        else
            $ret = new MysqliExceptionType();

        return $ret;
    }

    protected static function staticallyInitialize()
    {
        if( is_null(self::$lookupTable) )
        {
            self::$lookupTable = [];

            $connectionError = new MysqliExceptionType();
            $connectionError->setConnectionError( true );
            $lockError = new MysqliExceptionType();
            $lockError->setLockError( true );

            // innodb deadlock
            self::addClassificationPair( 'query', 1213, $lockError );
            self::addClassificationPair( 'mysqli_query', 1213, $lockError );
            self::addClassificationPair( 'mysqli_reap_async_query', 1213, $lockError );

            // lock wait timeout
            self::addClassificationPair( 'query', 1205, $lockError );
            self::addClassificationPair( 'mysqli_query', 1205, $lockError );
            self::addClassificationPair( 'mysqli_reap_async_query', 1205, $lockError );

            // error on mysqli_poll (not completed)
            self::addClassificationPair( 'poll', -1, $lockError );
            self::addClassificationPair( 'mysqli_poll', -1, $lockError );

            // Couldn't fetch mysqli
            self::addClassificationPair( 'query', 2, $connectionError );
            self::addClassificationPair( 'mysqli_query', 2, $connectionError );
            self::addClassificationPair( 'ping', 2, $connectionError );
            self::addClassificationPair( 'mysqli_ping', 2, $connectionError );
            self::addClassificationPair( 'mysqli_reap_async_query', 2, $connectionError );
        }
    }
}


//class NotCategorizedMysqliPollException extends MysqliException
//{
//    // use SqlStatementHoldExceptionTrait;
//
//    public static $defaultMessage = 'Not Categorized Exception on mysqli_poll function';
//    public function __construct($message = null,
//                                $code = 0, \Exception $previous = null) {
//        if( is_null($message) )
//            $message = self::$defaultMessage;
//        parent::__construct($message, $code, $previous);
//    }
//
//    public function __toString() {
//        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
//    }
//}

//class CouldntFetchMysqliException extends MysqliException
//{
//    // use SqlStatementHoldExceptionTrait;
//
//    public static $defaultMessage = 'Couldn' . "'" . 't fetch mysqli Exception on mysqli_poll function';
//    public function __construct($message = null,
//                                $code = 0, \Exception $previous = null) {
//        if( is_null($message) )
//            $message = self::$defaultMessage;
//        parent::__construct($message, $code, $previous);
//    }
//
//    public function __toString() {
//        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
//    }
//}


