<?php


class ConnectorUpdateMessage
{
    /**
     * @var null|\mysqli
     */
    protected $mysqli = null;

    /**
     * @var int
     */
    protected $retryCount = 0;
    /**
     * @var float
     */
    protected $startTime = -1.0;
    /**
     * @var float
     */
    protected $durationMilliseconds = -1.0;

    /**
     * @var bool
     */
    protected $cancelRetrying = false;

    // 正常系も入れる？どうする？
    // callback 側から中止を指示することは可能か？

    /**
     * Do not use
     *
     * @param mysqli $mysqli
     * @return ConnectorUpdateMessage
     */
    public function setMysqli( \mysqli $mysqli ): ConnectorUpdateMessage
    {
        $this->mysqli = $mysqli;
        return $this;
    }

    /**
     * Do not use
     *
     * @param float $startTime
     * @return ConnectorUpdateMessage
     */
    public function setStartTime( float $startTime ): ConnectorUpdateMessage
    {
        $this->startTime = $startTime;
        $currentTime = microTime( true );
        $this->durationMilliseconds = ($currentTime - $this->startTime)*1000;
        return $this;
    }

    /**
     * Same as mysqli->connect_errno of the last retry
     *
     * @see http://php.net/manual/en/mysqli.connect-errno.php
     * @return string
     */
    public function getErrorNo(): string
    {
        if( !is_null($this->mysqli) )
            return $this->mysqli->connect_errno;
        return '-1';
    }

    /**
     * Same as mysqli->connect_error of the last retry
     *
     * @see http://php.net/manual/en/mysqli.connect-error.php
     * @return string
     */
    public function getError(): string
    {
        if( !is_null($this->mysqli) )
            return $this->mysqli->connect_error;
        return 'ConnectorUpdateMessage initialization error';
    }

    /**
     * microtime(true) of 1st connect try.
     *
     * @return float
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * Elapsed time in milliseconds
     *
     * @return float
     */
    public function getDurationMilliseconds(): float
    {
        return $this->durationMilliseconds;
    }

    /**
     * How many times the connect method retry.
     *
     * The 1st time of this message, this function always returns 0. The next time, this function returns 1.
     *
     * @return int
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * Do not use
     *
     * @param int $retryCount
     */
    public function setRetryCount(int $retryCount)
    {
        $this->retryCount = $retryCount;
    }

    /**
     * Once this called, connection method will stop to retry.
     */
    public function orderCancel()
    {
        $this->cancelRetrying = true;
    }

    /**
     * Do not use
     */
    public function cancelOrdered()
    {
        return $this->cancelRetrying;
    }
}


