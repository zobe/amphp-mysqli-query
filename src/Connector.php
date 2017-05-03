<?php

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
     * @param bool $enableUpdateMessage set true to enable update message
     * @return \Amp\Promise
     */
    public function connectWithAutomaticRetry(ConnectionSettings $connectionSetting = null, RetrySettings $retrySetting = null, bool $enableUpdateMessage = false )
    {
        if( is_null($connectionSetting) )
            $connectionSetting = $this->defaultConnectionSetting;
        if( is_null($retrySetting) )
            $retrySetting = $this->defaultRetrySetting;

        $defer = new Amp\Deferred();

        $promise = Amp\resolve(
            function() use ( $defer, $connectionSetting, $retrySetting, $enableUpdateMessage )
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

                    $mysqli = @new mysqli(
                        $connectionSetting->getHost(),
                        $connectionSetting->getUser(),
                        $connectionSetting->getPassword(),
                        $connectionSetting->getDatabase(),
                        $connectionSetting->getPort(),
                        $connectionSetting->getSocket()
                    );

                    if( $mysqli->connect_error ) {
                        if ($mysqli->connect_errno) {
                            if ( $retrySetting->getMaxRetryCount() > 0 && $retryCount > $retrySetting->getMaxRetryCount() ) {
                                return $mysqli;
                            }

                            if( $enableUpdateMessage ) {
                                $updateMessage = new ConnectorUpdateMessage();
                                $updateMessage->setMysqli($mysqli);
                                $updateMessage->setStartTime($startTime);
                                $updateMessage->setRetryCount($retryCount);
                                $defer->update($updateMessage);
                                if ($updateMessage->cancelOrdered())
                                    return $mysqli;
                            }
                            yield new \Amp\Pause(
                                $retrySetting->getDelayMillisecondsOnRetry()
                                + mt_rand(0, $retrySetting->getDelayMillisecondsOnRetry() / 2) );
                            continue;
                        } else {
                            return $mysqli;
                        }
                    }
                    $finish_establish_connection = true;
                }
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
     * @param mysqli $mysqli requires mysqli object which has to be created by function \mysqli_init
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

        $defer = new Amp\Deferred();

        $promise = Amp\resolve(
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
                            return $mysqli;
                        }
                    }

                    assert( ($mysqli instanceof \mysqli) );
                    @$mysqli->real_connect(
                        $connectionSetting->getHost(),
                        $connectionSetting->getUser(),
                        $connectionSetting->getPassword(),
                        $connectionSetting->getDatabase(),
                        $connectionSetting->getPort(),
                        $connectionSetting->getSocket(),
                        $flags
                    );

                    if( $mysqli->connect_error ) {
                        if ($mysqli->connect_errno) {
                            if ( $retrySetting->getMaxRetryCount() > 0 && $retryCount > $retrySetting->getMaxRetryCount() ) {
                                return $mysqli;
                            }

                            if( $enableUpdateMessage ) {
                                $updateMessage = new ConnectorUpdateMessage();
                                $updateMessage->setMysqli($mysqli);
                                $updateMessage->setStartTime($startTime);
                                $updateMessage->setRetryCount($retryCount);
                                $defer->update($updateMessage);
                                if ($updateMessage->cancelOrdered())
                                    return $mysqli;
                            }
                            yield new \Amp\Pause(
                                $retrySetting->getDelayMillisecondsOnRetry()
                                + mt_rand(0, $retrySetting->getDelayMillisecondsOnRetry() / 2) );
                            continue;
                        } else {
                            return $mysqli;
                        }
                    }
                    $finish_establish_connection = true;
                }
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



