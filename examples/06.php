<?php
/**
 * transaction sample
 *
 * 10-second-locker: queries 'select for update' with a transaction and hold 10 seconds
 * 2-seconds-behind-selector: queries 'select for update' against locked table,
 *   and is forced to wait, and get the result modified values by the above.
 */


require '../vendor/autoload.php';
require_once '../src/Query.php';
require_once './config.php';


function DumpQueryResult( \zobe\AmphpMysqliQuery\Result $result, string $displayPrefix = '' )
{
    $mysqliResult = $result->getResult();
    if( is_null($mysqliResult) ) {
        echo $displayPrefix;
        echo ': ';
        echo 'Result->getResult is null' . PHP_EOL;
        echo $displayPrefix;
        echo ': ';
        echo 'Result->getResultRaw: ';
        echo var_dump($result->getResultRaw());
        return;
    }
    $row = mysqli_fetch_row( $mysqliResult );
    while( !is_null($row) )
    {
        foreach( $row as $key => $value )
        {
            echo $key;
            echo ' => ';
            echo $value;
            echo ', ';
        }
        echo PHP_EOL;
        $row = mysqli_fetch_row( $mysqliResult );
    }
}


function DemoQuery( \zobe\AmphpMysqliQuery\Query $query, \mysqli $link, string $sql, string $displayPrefix = '' )
{
    echo $displayPrefix;
    echo ': ';
    echo 'sql: [' . $sql . '] start' . PHP_EOL;
    $ret = yield $query->query( $link, $sql );
    echo $displayPrefix;
    echo ': ';
    echo 'sql: [' . $sql . '] end' . PHP_EOL;

    echo $displayPrefix;
    echo ': ';
    echo 'result: ' .PHP_EOL;
    if( $ret instanceof \zobe\AmphpMysqliQuery\Result )
    {
        DumpQueryResult( $ret, $displayPrefix );
        if( !is_null($ret->getResult()) )
            mysqli_free_result( $ret->getResult() );
    }
    echo $displayPrefix;
    echo ': ';
    echo 'mysqli::affected_rows: ' . $link->affected_rows . PHP_EOL;
    echo PHP_EOL;
}


function SetupPrerequisites()
{
    return Amp\resolve(
        function()
        {
            $query = new \zobe\AmphpMysqliQuery\Query();
            $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );

            $displayPrefix = 'setup prerequisites';

            $sql = 'drop table if exists tmp_amphpmysqliquery_examples_06';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);
            $sql = 'create table if not exists tmp_amphpmysqliquery_examples_06 (id varchar(16), val int)';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);

            $sql = "insert into tmp_amphpmysqliquery_examples_06 values ('ID1', 101), ('ID2', 102)";
            yield from DemoQuery($query,$link, $sql, $displayPrefix);
            $sql = "insert into tmp_amphpmysqliquery_examples_06 values ('ID3', 103), ('ID4', 104)";
            yield from DemoQuery($query,$link, $sql, $displayPrefix);

            mysqli_close( $link );
        }
        );
}


function Clean()
{
    return Amp\resolve(
        function()
        {
            $query = new \zobe\AmphpMysqliQuery\Query();
            $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );

            $displayPrefix = 'Clean';

            $sql = 'drop table if exists tmp_amphpmysqliquery_examples_06';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);

            mysqli_close( $link );
        }
    );
}


function Demo10SecondsLocker()
{
    return Amp\resolve(
        function()
        {
            $query = new \zobe\AmphpMysqliQuery\Query();
            $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );

            $displayPrefix = '10-second-locker';

            $sql = 'start transaction';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);
            $sql = 'select * from tmp_amphpmysqliquery_examples_06 for update';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);
            $sql = 'select sleep(10)';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);
            $sql = 'update tmp_amphpmysqliquery_examples_06 set val=val+1';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);
            $sql = 'commit';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);

            mysqli_close( $link );
        }
    );
}

function Demo2SecondsBehindSelector()
{
    return Amp\resolve(
        function()
        {
            $query = new \zobe\AmphpMysqliQuery\Query();
            $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );

            $displayPrefix = '2-seconds-behind-selector';

            $sql = 'select sleep(2)';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);
            echo $displayPrefix . ': query below is expected to wait the transaction'.PHP_EOL;
            $sql = 'select * from tmp_amphpmysqliquery_examples_06 for update';
            yield from DemoQuery($query,$link, $sql, $displayPrefix);

            mysqli_close( $link );
        }

    );
}



Amp\run(
    function()
    {
        echo PHP_EOL;
        echo '*** setup demo table and data ***'.PHP_EOL;
        echo PHP_EOL;
        yield SetupPrerequisites();

        echo PHP_EOL;
        echo PHP_EOL;
        echo '*** demo start ***'.PHP_EOL;
        echo PHP_EOL;
        yield Amp\all( [Demo10SecondsLocker(), Demo2SecondsBehindSelector()] );

        echo PHP_EOL;
        echo PHP_EOL;
        echo '*** terminating ***' . PHP_EOL;
        yield Clean();
    }
);

