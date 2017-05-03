<?php
/**
 * Connector class sample mock
 */

require '../vendor/autoload.php';
require_once '../src/Connector.php';
require_once '../src/RetrySettings.php';
require_once '../src/ConnectionSettings.php';
require_once '../src/ConnectorUpdateMessage.php';
require_once './config.php';



Amp\run(
    function()
    {
        $ctr = new Connector();
        $ctr->setDefaultConnectionSetting(
            new ConnectionSettings(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            )
        );



        ///
        // sample 1

        // open
        $c = yield $ctr->connectWithAutomaticRetry();

        // close
        assert( ($c instanceof \mysqli) );
        if( !$c->connect_error )
        {
            $c->close();
        }



        ///
        // sample 2 - receiving update message and canceling operation

        // open
        $p = $ctr->connectWithAutomaticRetry();
        $p = $p->watch(
            function( ConnectorUpdateMessage $msg )
            {
                static $count = 0;
                $maxCount = 10;
                $count++;

                if( $msg->getRetryCount() > $maxCount )
                    $msg->orderCancel();
            }
        );
        $c = yield $p;

        // close
        assert( ($c instanceof \mysqli) );
        if( !$c->connect_error )
        {
            $c->close();
        }


        ///
        // sample 3 - real_connect
        $c = \mysqli_init();
        $c->options( MYSQLI_OPT_CONNECT_TIMEOUT, 30 );
        // $c->options() ...
        yield $ctr->realConnectWithAutomaticRetry( $c );
        // close
        assert( ($c instanceof \mysqli) );
        if( !$c->connect_error )
        {
            $c->close();
        }
    }
);


