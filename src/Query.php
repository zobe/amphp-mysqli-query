<?php

namespace zobe\AmphpMysqliQuery;

require '../vendor/autoload.php';
require __DIR__ . '/QueryInfo.php';
require __DIR__ . '/Result.php';
require __DIR__ . '/Exceptions.php';

use Amp;

class Query
{
    // singleton...?
    protected static $singletons = [];

    /**
     * You can get Query instance
     *
     * @param Amp\Reactor|null $reactor
     * @return mixed
     */
    public static function getSingleton(Amp\Reactor $reactor = null )
    {
        if( is_null($reactor) )
        {
            $reactor = Amp\reactor();
        }

        $id = spl_object_hash($reactor);
        if( !array_key_exists( $id, self::$singletons ) )
        {
            self::$singletons[$id] = new Query($reactor);
        }
        return self::$singletons[$id];
    }


    /**
     * @var QueryInfo[]
     */
    protected $queries;

    /**
     * @var \Amp\Reactor
     */
    protected $reactor;

    /**
     * @var string WatcherID of queryTick()
     */
    protected $queryLoopWatcherId;

    /**
     * Constructor.
     *
     * Usually you should not use it but Query::getSingleton().
     *
     * If no reactor has been set, automatically chosen default reactor.
     *
     * @param \Amp\Reactor|null $reactor
     */
    function __construct(Amp\Reactor $reactor = null )
    {
        $this->reactor = $reactor;
        if( is_null($reactor) )
            $this->reactor = Amp\reactor();

        $this->queries = [];
        $this->queryLoopWatcherId = null;
    }

    /**
     * Yield me to execute sql asynchronously.
     *
     * @param \mysqli $mysqli Target mysqli object. Pay attention to use 1 query for this at once.
     * @param string $sql
     * @return mixed Yield yields Amp Promise object. Yielder can get MysqliAsyncQueryResult object.
     * @throws \InvalidArgumentException mysqli object is already used for a query
     */
    function query(\mysqli $mysqli, string $sql )
    {
        $id = spl_object_hash( $mysqli );
        if( array_key_exists( $id, $this->queries ) )
        {
            throw new \InvalidArgumentException( 'This mysqli object is already used for a query. You can use only one query at a time. If you want to execute 2 sqls at a time, 2 connections required.' );
        }

        $mysqli->query( $sql, MYSQLI_ASYNC );

        $query = new QueryInfo();
        $query->setSql( $sql );
        $this->queries[$id] = $query;

        $query->setConnection( $mysqli );
        $query->setSql( $sql );

        if( is_null($this->queryLoopWatcherId) )
        {
            $this->queryLoopWatcherId = $this->reactor->repeat( [$this,'queryTick'], 10 );
        }

        return $query->getDefer()->promise();
    }

    public static function errorHandlerOnMysqliPoll( $errno, $errstr, $errfile, $errline )
    {
        if( $errno === 2 &&
            strpos( $errstr, 'mysqli_poll' ) !== false &&
            strpos( $errstr, "Couldn't fetch mysqli" ) !== false
        )
            throw new CouldntFetchMysqliException($errstr . ', file: ' . $errfile . ', line: ' . $errline, $errno);

        throw new NotCategorizedMysqliPollException(
            $errstr . ', file: ' . $errfile . ', line: ' . $errline,
            $errno);
    }

    protected function destroyZombieConnections()
    {
        foreach( $this->queries as $id => $queryInfo )
        {
            $links = $errors = $rejects = [];
            $conn = $queryInfo->getConnection();
            $links[] = $errors[] = $rejects[] = $conn;

            try {
                set_error_handler( ['zobe\\AmphpMysqliQuery\\Query','errorHandlerOnMysqliPoll'] );
                $poll_result = mysqli_poll($links, $errors, $rejects, 0);
            }
            catch( CouldntFetchMysqliException $e )
            {
                $e->setSql( $this->queries[$id]->getSql() );
                $queryInfo->getDefer()->fail( $e );
                unset( $this->queries[$id] );
            }
            catch( NotCategorizedMysqliPollException $e )
            {
                $e->setSql( $this->queries[$id]->getSql() );
                $queryInfo->getDefer()->fail( $e );
                unset( $this->queries[$id] );
            }
            catch( \Throwable $e )
            {
                $err = new NotCategorizedMysqliPollException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e
                );
                $err->setSql( $this->queries[$id]->getSql() );
                $queryInfo->getDefer()->fail( $err );
                unset( $this->queries[$id] );
            }
            finally
            {
                restore_error_handler();
            }
        }
    }

    /**
     * Do not call me. Automatically called if one or more queryinfo object(s) exist(s).
     */
    function queryTick()
    {
        $a = $links = $errors = $rejects = [];
        $poll_result = false;

        foreach( $this->queries as $q )
        {
            $links[] = $errors[] = $rejects[] = $q->getConnection();
        }

        try {
            set_error_handler( ['zobe\\AmphpMysqliQuery\\Query','errorHandlerOnMysqliPoll'] );
            $poll_result = mysqli_poll($links, $errors, $rejects, 0);
        }
        catch( \Throwable $e )
        {
            $this->destroyZombieConnections();
            $this->terminateLoopIfNoQueryExists();
        }
        finally
        {
            restore_error_handler();
        }
        if( !$poll_result ) {
            // mysqli_poll() returns false therefore I suspend myself until next tick
            return;
        }

        $processedIds = [];
        foreach( $links as $link )
        {
            $id = spl_object_hash($link);
            $query = $this->queries[$id];
            $processedIds[] = $id;

            if( $result = mysqli_reap_async_query( $link ) )
            {
                $queryResult = new Result();
                $queryResult->setSql( $query->getSql() );
                $queryResult->setResult( $result );
                $query->getDefer()->succeed( $queryResult );
            }
            else
            {
                $e = new MysqliException(mysqli_error($link),mysqli_errno($link));
                $e->setSql( $this->queries[$id]->getSql() );
                $query->getDefer()->fail($e);
            }
        }

        foreach( $processedIds as $id )
        {
            unset( $this->queries[$id] );
        }

        // terminate loop if no query exists
        $this->terminateLoopIfNoQueryExists();
    }

    protected function terminateLoopIfNoQueryExists()
    {
        if( count($this->queries) <= 0 ) {
            $this->reactor->cancel( $this->queryLoopWatcherId );
            $this->queryLoopWatcherId = null;
        }
    }
}


