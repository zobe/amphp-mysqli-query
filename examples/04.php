<?php
/**
 * Invalid sql query and exception sample
 *
 * AmphpMysqliQuery throws MysqliException or its child.
 *
 */

require_once '../src/Query.php';
require_once './config.php';

$query = new \zobe\AmphpMysqliQuery\Query();

$promises = [];
$promises[] = Amp\resolve(
    function() use ($query)
    {
        $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );
        $sql = 'select 1+2+3+4+5+6+7+8+9+...Ah-choo!!';
        echo $sql . ' start' . PHP_EOL;
        $ret = null;

        try {
            $result = yield $query->query($link, $sql);
        }
        catch( \Throwable $e )
        {
            // to simply kill coroutine and send error to main:
            throw $e;


            // or you can forget exception to force to retry coroutine
            // ex. while(){
            //   $link=mysqli_connect();
            //   try
            //   {
            //     $result=yield $query->query();
            //     break;
            //   }
            //   catch(){ yield new Amp\Pause(1000); continue; }
            // }
        }

        if( $result instanceof \zobe\AmphpMysqliQuery\Result ) {
            $row = mysqli_fetch_row($result->getResult() );
            while (!is_null($row)) {
                $count = 0;
                foreach ($row as $aValue) {
                    $count++;
                    if ($count > 1)
                        echo ', ';
                    echo $aValue;
                    $ret = $aValue;
                }
                echo PHP_EOL;

                $row = mysqli_fetch_row( $result->getResult() );
            }
            mysqli_free_result( $result->getResult() );
        }

        echo $sql . ' end' . PHP_EOL;
        if( is_null($ret) )
            throw new Exception( 'No result?' );
        return $ret;
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


