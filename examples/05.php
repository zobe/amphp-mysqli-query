<?php
/**
 * basic query sample
 *
 * create table, insert, update, select, drop table
 */


require '../vendor/autoload.php';
require_once '../src/Query.php';
require_once './config.php';


function AmphpMysqliQueryDump( \zobe\AmphpMysqliQuery\Result $result )
{
    $mysqliResult = $result->getResult();
    if( is_null($mysqliResult) ) {
        echo 'mysqliResult is null' . PHP_EOL;
        echo 'mysqliResultRaw: ';
        echo var_dump($result->getResultRaw());
        return;
    }
    if( $mysqliResult instanceof \mysqli_result ) {
        $row = mysqli_fetch_row($mysqliResult);
        while (!is_null($row)) {
            foreach ($row as $key => $value) {
                echo $key;
                echo ' => ';
                echo $value;
                echo ', ';
            }
            echo PHP_EOL;
            $row = mysqli_fetch_row($mysqliResult);
        }
    }
    else
        var_dump( $mysqliResult );
}


function DemoQuery( \zobe\AmphpMysqliQuery\Query $query, \mysqli $link, string $sql, \zobe\AmphpMysqliQuery\QueryType $queryType = null )
{
    echo 'sql: ' . $sql . PHP_EOL;
    $ret = yield $query->query( $link, $sql, $queryType );

    echo 'result: ' .PHP_EOL;
    if( $ret instanceof \zobe\AmphpMysqliQuery\Result )
    {
        AmphpMysqliQueryDump( $ret );
        if( !is_null($ret->getResult()) && $ret->getResult() instanceof \mysqli_result )
            mysqli_free_result( $ret->getResult() );
    }
    echo 'mysqli::affected_rows: ' . $link->affected_rows . PHP_EOL;
    echo PHP_EOL;
}


Amp\run(
    function()
    {
        $query = new \zobe\AmphpMysqliQuery\Query();
        $link = mysqli_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME );


        $sql = 'select 3';
        yield from DemoQuery($query,$link, $sql);

        $sql = 'create table if not exists tmp_amphpmysqliquery_examples_05 (id varchar(16), val int)';
        yield from DemoQuery($query,$link, $sql, \zobe\AmphpMysqliQuery\QueryType::typeExecOnly() );

        $sql = "insert into tmp_amphpmysqliquery_examples_05 values ('ID1', 101), ('ID2', 102)";
        yield from DemoQuery($query,$link, $sql, \zobe\AmphpMysqliQuery\QueryType::typeExecOnly() );

        $sql = "select * from tmp_amphpmysqliquery_examples_05 where id = 'ID2'";
        yield from DemoQuery($query,$link, $sql, \zobe\AmphpMysqliQuery\QueryType::typeFirstRowOnly() );

        $sql = "update tmp_amphpmysqliquery_examples_05 set val = 1020 where id = 'ID2'";
        yield from DemoQuery($query,$link, $sql, \zobe\AmphpMysqliQuery\QueryType::typeExecOnly() );

        $sql = "select * from tmp_amphpmysqliquery_examples_05 where id = 'ID2'";
        yield from DemoQuery($query,$link, $sql, \zobe\AmphpMysqliQuery\QueryType::typeNormal() );

        $sql = 'drop table if exists tmp_amphpmysqliquery_examples_05';
        yield from DemoQuery($query,$link, $sql, \zobe\AmphpMysqliQuery\QueryType::typeExecOnly() );
    }
);

