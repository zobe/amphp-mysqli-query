<?php

namespace zobe\AmphpMysqliQuery;

use Amp\Pause;

require __DIR__ . './ConnectionSettings.php';
require __DIR__ . './RetrySettings.php';
require __DIR__ . './ConnectorTaskInfo.php';

/**
 * This represents connection factory methods with asynchronous retry mechanism.
 */
class Connector
{
    protected $defaultConnectionSetting = null;
    protected $defaultRetrySetting = null;

    public function __construct()
    {
        $this->defaultConnectionSetting = new ConnectionSettings();
        $this->defaultRetrySetting = new RetrySettings();
    }

    public function errorHandlerOnPing( $errno, $errstr, $errfile, $errline )
    {
        $e = new MysqliException(
            'Error at mysqli_ping(): ' . $errstr . ', file: ' . $errfile . ', line: ' . $errline,
            $errno);
        $e->setMethodName( 'ping' );
        $e->setClassName( 'mysqli' );
        $e->setConnectionError(true);
        throw $e;
    }

    /**
     * Tries to make connection using mysqli constructor.
     *
     * This method is asynchronous on retry.
     * This method is cancelable using ConnectorUpdateMessage with Promise->update(). Set enableUpdateMessage true to get update message.
     *
     * Regardress of succeeded or failed, yielded return value and succeed value are mysqli object.
     * Same as normal operation to use mysqli_connect(), check $mysqli->connect_error to make sure of successful or failure.
     *
     * @see http://php.net/manual/en/mysqli.construct.php
     *
     * @param ConnectionSettings|null $connectionSetting if null, the value of $this->setDefaultConnectionSetting() is used.
     * @param RetrySettings|null $retrySetting if null, the value of $this->setDefaultRetrySetting() is used.
     * @param callable $update A callback to invoke when data updates are available. The callback will be called with 1 ConnectorTaskInfo or the successor. Callback example: function(ConnectorTaskInfo $inf){;}
     * @return \Amp\Promise
     */
    public function connectWithAutomaticRetry(ConnectionSettings $connectionSetting = null, RetrySettings $retrySetting = null, callable $update = null )
    {
        if( is_null($connectionSetting) )
            $connectionSetting = $this->defaultConnectionSetting;
        if( is_null($retrySetting) )
            $retrySetting = $this->defaultRetrySetting;

        $defer = new \Amp\Deferred();

        $promise = \Amp\resolve(
            function() use ( $defer, $connectionSetting, $retrySetting, $update )
            {
                $finish_establish_connection = false;
                $retryCount = -1;
                $mysqli = null;
                $startTime = microtime(true);
                $timeoutSec = ((float)($retrySetting->getTimeoutMilliseconds()))/1000;

                while( !$finish_establish_connection )
                {
                    $retryCount++;
                    if( $retryCount > 0 && $retrySetting->getTimeoutMilliseconds() > 0 )
                    {
                        $currentTime = microtime( true );
                        if( $currentTime - $startTime > $timeoutSec )
                        {
                            return $mysqli;
                        }
                    }

                    $host = $connectionSetting->getHost();
                    if( !(strpos( $host, 'p:' ) === 0) )
                        $host = 'p:' . $host;
                    $mysqli = @new \mysqli(
                        $host,
                        $connectionSetting->getUser(),
                        $connectionSetting->getPassword(),
                        $connectionSetting->getDatabase(),
                        $connectionSetting->getPort(),
                        $connectionSetting->getSocket()
                    );

                    try {
                        set_error_handler( [$this,'errorHandlerOnPing'] );
                        $mysqli->ping();
                    }
                    catch( MysqliException $e )
                    {
                        if( is_callable($update) ) {
                            $updateMessage = ConnectorTaskInfoFactory::createExceptionSuppressed( $mysqli, $startTime, $retryCount, $e,
                                'Connector::connectWithAutomaticRetry(): Suppressing an exception',
                                'Code: ' . $e->getCode() . ', Msg: ' . $e->getMessage()
                            );
                            call_user_func( $update, $updateMessage );
                            if ($updateMessage->isCancelOrdered_CancelableTaskInfoTrait()) {
                                return $mysqli;
                            }
                        }
                        yield new \Amp\Pause(
                            $retrySetting->getDelayMillisecondsOnRetry()
                            + mt_rand(0, $retrySetting->getDelayMillisecondsOnRetry() / 100) );
                        continue;
                    }
                    finally
                    {
                        restore_error_handler();
                    }

                    if( $mysqli->connect_error ) {
                        if ( $retrySetting->getMaxRetryCount() > 0 && $retryCount > $retrySetting->getMaxRetryCount() ) {
                            return $mysqli;
                        }

                        if( is_callable($update) ) {
                            $err = new MysqliException('connect_error has been discovered after successful mysqli ctor: ' . $mysqli->connect_error, $mysqli->connect_errno);
                            $updateMessage = ConnectorTaskInfoFactory::createExceptionSuppressed( $mysqli, $startTime, $retryCount, $err,
                                'Connector::connectWithAutomaticRetry(): Suppressing an exception',
                                'Code: ' . $err->getCode() . ', Msg: ' . $err->getMessage()
                            );
                            call_user_func( $update, $updateMessage );
                            if ($updateMessage->isCancelOrdered_CancelableTaskInfoTrait()) {
                                return $mysqli;
                            }
                        }
                        yield new \Amp\Pause(
                            $retrySetting->getDelayMillisecondsOnRetry()
                            + mt_rand(0, $retrySetting->getDelayMillisecondsOnRetry() / 100) );
                        continue;
                    }
//                    else
//                    {
//                        try {
//                            set_error_handler( [$this,'errorHandlerOnPing'] );
//                            $mysqli->ping();
//                        }
//                        catch( MysqliException $e )
//                        {
//                            if( $enableUpdateMessage ) {
//                                $updateMessage = new ExceptionSuppressedConnectionTaskInfo();
//                                $updateMessage->setMysqli_MysqliTaskInfoTrait($mysqli);
//                                $updateMessage->setStartTime_LifeTimeTaskInfoTrait($startTime);
//                                $updateMessage->setRetryCount_ConnectorTaskInfo($retryCount);
//                                $updateMessage->setSuppressedException_ExceptionSuppressedTaskInfoTrait($e);
//                                $defer->update($updateMessage);
//                                if ($updateMessage->isCancelOrdered_CancelableTaskInfoTrait()) {
//                                    return $mysqli;
//                                }
//                            }
//                            yield new \Amp\Pause(
//                                $retrySetting->getDelayMillisecondsOnRetry()
//                                + mt_rand(0, $retrySetting->getDelayMillisecondsOnRetry() / 2) );
//                            continue;
//                        }
//                        finally
//                        {
//                            restore_error_handler();
//                        }
//                    }
                    $finish_establish_connection = true;
                }
                if( is_callable($update) ) {
                    $updateMessage = ConnectorTaskInfoFactory::create( $mysqli, $startTime, $retryCount,
                        'Connector::connectWithAutomaticRetry(): Successfully completed' );
                    $updateMessage->setEndTimeLife_TimeTaskInfoTrait();
                    call_user_func( $update, $updateMessage );
                }
                return $mysqli;
            }
        );

        $promise->when(
            function( $error = null, $result = null )
            use ($defer)
            {
                if( $error ) {
                    $defer->fail($error);
                }
                else
                    $defer->succeed( $result );
            }
        );

        return $defer->promise();
    }

    /**
     * Tries to make connection using mysqli_real_connect().
     *
     * This method is asynchronous on retrying.
     * This method is cancelable using ConnectorUpdateMessage with Promise->update(). Set enableUpdateMessage true to get update message.
     *
     * Regardress of succeeded or failed, yielded return value and succeed value are mysqli object.
     * Same as normal operation to use mysqli_connect(), check $mysqli->connect_error to make sure of successful or failure.
     *
     * @see http://php.net/manual/en/mysqli.real-connect.php
     * @see http://php.net/manual/en/mysqli.init.php
     *
     * @param \mysqli $mysqli requires mysqli object which has to be created by function \mysqli_init
     * @param int $flags same as flags parameter of function \mysqli_real_connect
     * @param ConnectionSettings|null $connectionSetting if null, the value of $this->setDefaultConnectionSetting() is used.
     * @param RetrySettings|null $retrySetting if null, the value of $this->setDefaultRetrySetting() is used.
     * @param bool $enableUpdateMessage set true to enable update message
     * @return \Amp\Promise
     */
    public function realConnectWithAutomaticRetry(\mysqli $mysqli, int $flags = 0, ConnectionSettings $connectionSetting = null, RetrySettings $retrySetting = null, bool $enableUpdateMessage = false )
    {
        if( is_null($connectionSetting) )
            $connectionSetting = $this->defaultConnectionSetting;
        if( is_null($retrySetting) )
            $retrySetting = $this->defaultRetrySetting;

        $defer = new \Amp\Deferred();

        $promise = \Amp\resolve(
            function() use ( $defer, $mysqli, $flags, $connectionSetting, $retrySetting, $enableUpdateMessage )
            {
                $finish_establish_connection = false;
                $retryCount = -1;
                $startTime = microtime(true);
                $timeoutSec = ((float)($retrySetting->getTimeoutMilliseconds()))/1000;

                while( !$finish_establish_connection )
                {
                    $retryCount++;
                    if( $retryCount > 0 && $retrySetting->getTimeoutMilliseconds() > 0 )
                    {
                        $currentTime = microtime( true );
                        if( $currentTime - $startTime > $timeoutSec )
                        {
//                            echo '[A]';
                            return $mysqli;
                        }
                    }

                    assert( ($mysqli instanceof \mysqli) );
                    $result = @$mysqli->real_connect(
                        $connectionSetting->getHost(),
                        $connectionSetting->getUser(),
                        $connectionSetting->getPassword(),
                        $connectionSetting->getDatabase(),
                        $connectionSetting->getPort(),
                        $connectionSetting->getSocket(),
                        $flags
                    );

                    yield new \Amp\Pause(5000);
                    if( $mysqli->connect_error ) {
                        if ($mysqli->connect_errno) {
                            if ( $retrySetting->getMaxRetryCount() > 0 && $retryCount > $retrySetting->getMaxRetryCount() ) {
//                                echo '[B]';
                                return $mysqli;
                            }

                            if( $enableUpdateMessage ) {
                                $updateMessage = new ConnectorTaskInfo();
                                $updateMessage->setMysqli($mysqli);
                                $updateMessage->setStartTime($startTime);
                                $updateMessage->setRetryCount_ConnectorTaskInfo($retryCount);
                                $defer->update($updateMessage);
                                if ($updateMessage->cancelOrdered()) {
//                                    echo '[F1]';
                                    return $mysqli;
                                }
                            }
                            yield new \Amp\Pause(
                                $retrySetting->getDelayMillisecondsOnRetry()
                                + mt_rand(0, $retrySetting->getDelayMillisecondsOnRetry() / 2) );
                            continue;
                        } else {
//                            echo '[C]';
                            return $mysqli;
                        }
                    }
                    if( $result === FALSE )
                    {
                        if ( $retrySetting->getMaxRetryCount() > 0 && $retryCount > $retrySetting->getMaxRetryCount() ) {
//                            echo '[E]';
                            return $mysqli;
                        }

                        if( $enableUpdateMessage ) {
                            $updateMessage = new ConnectorTaskInfo();
                            $updateMessage->setMysqli($mysqli);
                            $updateMessage->setStartTime($startTime);
                            $updateMessage->setRetryCount_ConnectorTaskInfo($retryCount);
                            $defer->update($updateMessage);
                            if ($updateMessage->cancelOrdered()) {
//                                echo '[F2]';
                                return $mysqli;
                            }
                        }
                        yield new \Amp\Pause(
                            $retrySetting->getDelayMillisecondsOnRetry()
                            + mt_rand(0, $retrySetting->getDelayMillisecondsOnRetry() / 2) );
                        continue;
                    }
//                    echo '{A}';
                    $finish_establish_connection = true;
                }
//                echo '[D]';
                return $mysqli;
            }
        );

        $promise->when(
            function( \Exception $error = null, $result = null )
            use ($defer)
            {
                if( $error ) {
                    $defer->fail($error);
                }
                else
                    $defer->succeed( $result );
            }
        );

        return $defer->promise();
    }

    /**
     * @return ConnectionSettings
     */
    public function getDefaultConnectionSetting()
    {
        return $this->defaultConnectionSetting;
    }

    /**
     * @param ConnectionSettings $defaultConnectionSetting
     */
    public function setDefaultConnectionSetting(ConnectionSettings $defaultConnectionSetting)
    {
        $this->defaultConnectionSetting = $defaultConnectionSetting;
    }

    /**
     * @return RetrySettings
     */
    public function getDefaultRetrySetting()
    {
        return $this->defaultRetrySetting;
    }

    /**
     * @param RetrySettings $defaultRetrySetting
     */
    public function setDefaultRetrySetting(RetrySettings $defaultRetrySetting)
    {
        $this->defaultRetrySetting = $defaultRetrySetting;
    }
}



