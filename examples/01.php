<?php
/**
 * parallel execution test
 */

require '../vendor/autoload.php';
require_once '../src/Query.php';
require_once './config.php';


$promises = [];
$promises[] = Amp\resolve(
    function()
    {
        $query = \zobe\AmphpMysqliQuery\Query::getSingleton();
        $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );
        $sql = 'select sleep(1)';
        echo $sql . ' start' . PHP_EOL;
        yield $query->query( $link, $sql );
        echo $sql . ' end' . PHP_EOL;
        return '[sleep(1) executed]';
    }
);
$promises[] = Amp\resolve(
    function()
    {
        $query = \zobe\AmphpMysqliQuery\Query::getSingleton();
        $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );
        $sql = 'select sleep(2)';
        echo $sql . ' start' . PHP_EOL;
        yield $query->query( $link, $sql );
        echo $sql . ' end' . PHP_EOL;
        return '[sleep(2) executed]';
    }
);
$promises[] = Amp\resolve(
    function()
    {
        $query = \zobe\AmphpMysqliQuery\Query::getSingleton();
        $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );
        $sql = 'select sleep(3)';
        echo $sql . ' start' . PHP_EOL;
        yield $query->query( $link, $sql );
        echo $sql . ' end' . PHP_EOL;
        return '[sleep(3) executed]';
    }
);



Amp\run(
    function() use ($promises)
    {
        $startTime = microtime(true);

        // how to wait for each promise
        foreach( $promises as $p )
        {
            if( $p instanceof \Amp\Promise )
                $p->when(
                    function( $e, $r )
                    {
                        if( is_null($e) )
                        {
                            echo 'succeeded'.PHP_EOL;
                            echo 'result: ';
                            var_dump( $r );
                        }
                        else
                        {
                            echo 'failed'.PHP_EOL;
                            if( $e instanceof \Throwable ) {
                                echo get_class($e) . ', ';
                                echo $e->getMessage() . PHP_EOL;
                            }
                        }
                    }
                );
        }

        // how to wait all promises
        Amp\any($promises)->when(
            function() use ($startTime)
            {
                $endTime = microtime(true);
                echo 'all queries completed'.PHP_EOL;
                echo 'elapsed time: ' . ($endTime - $startTime) . PHP_EOL;
            } );
    }
);


