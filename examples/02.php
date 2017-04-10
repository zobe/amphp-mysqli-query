<?php
/**
 * accident simulation - connection break on running
 *
 * aimed at catching accidents correctly
 */

require_once '../src/Query.php';
require_once './config.php';

$link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );
if (!$link) {
    die('Connect Error (' . mysqli_connect_errno() . ') '
        . mysqli_connect_error());
}
$promises = [];

// 1st coroutine
// executes 'select sleep(10)'
// but the connection will be closed by 2nd coroutine after 2 seconds
// therefore the sql query will be broken and
// this coroutine sends exception via the promise
$promises[] = Amp\resolve(
    function() use ($link)
    {
        $echo_prefix = 'coroutine1';

        $query = \zobe\AmphpMysqliQuery\Query::getSingleton();
        $error = null;
        $sql = 'select sleep(10)';
        echo $echo_prefix . ': ' . $sql . ' start' . PHP_EOL;
        try {
            yield $query->query($link, $sql);
        }catch( \Throwable $e )
        {
            echo $echo_prefix . ': exception caught at yield call.'.PHP_EOL;
            echo $echo_prefix . ': get_class: ' . get_class($e).PHP_EOL;
            echo $echo_prefix . ': ' . $e->getMessage();
            echo PHP_EOL;
            $error = $e;
        }
        echo $echo_prefix . ': ' . $sql . ' end' . PHP_EOL;
        if( is_null($error) )
            return '[' . $echo_prefix . ': sleep(10) executed]'; // this promise will be resolved by this str
        throw $e; // this promise will fail with this object
    }
);

// 2nd coroutine
// this breaks mysqli link 1nd coroutine uses
// after 2000 milliseconds
$promises[] = Amp\resolve(
    function() use ($link)
    {
        $echo_prefix = 'coroutine2';

        echo $echo_prefix . ': ' . 'Amp\\Pause(2000) and link close' . ' start' . PHP_EOL;
        yield new \Amp\Pause( 2000 );
        $link->close(); // you can test same case to stop mysql service
        return '[' . $echo_prefix . ': Amp\\Pause(2000) and link close executed]';
    }
);

// 3rd coroutine
// no relations with 1st and 2nd coroutine
$promises[] = Amp\resolve(
    function() { // this coroutine will be successfully complete to sleep 5 seconds
        $echo_prefix = 'coroutine3';

        $query = \zobe\AmphpMysqliQuery\Query::getSingleton();
        $exception = null;
        $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$link) {
            return new Exception( $echo_prefix . ': Connect Error (' . mysqli_connect_errno() . ') '
                . mysqli_connect_error());
        }
        $sql = 'select sleep(5)';
        echo $echo_prefix . ': ' . $sql . ' start' . PHP_EOL;
        try {
            yield $query->query($link, $sql);
        } catch (\Throwable $e) {
            echo $echo_prefix . ': exception caught at yield call.' . PHP_EOL;
            echo $echo_prefix . ': ' . $e->getMessage();
            $exception = $e;
        }

        if (is_null($exception)) {
            echo $echo_prefix . ': ' . $sql . ' end' . PHP_EOL;
            return '[' . $echo_prefix . ': sleep(5) executed]';
        }
        return $exception;
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
                            echo 'main: promise->when(): succeeded'.PHP_EOL;
                            echo 'main: promise->when(): result: ';
                            var_dump( $r );
                        }
                        else
                        {
                            echo 'main: promise->when(): failed'.PHP_EOL;
                            if( $e instanceof \Throwable ) {
                                echo 'main: promise->when(): reason: ';
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
                echo 'main: Amp\\any($promises)->when(): all queries completed'.PHP_EOL;
                echo 'main: Amp\\any($promises)->when(): elapsed time: ' . ($endTime - $startTime) . PHP_EOL;
            } );
    }
);


