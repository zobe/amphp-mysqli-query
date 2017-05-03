<?php

namespace zobe\AmphpMysqliQuery;

/**
 * Retrying settings for connect methods of Connector class
 */
class RetrySettings
{
    protected $maxRetryCount = 0;
    protected $timeoutMilliseconds = 10000;
    protected $delayMillisecondsOnRetry = 1000;

    /**
     * RetrySettings constructor.
     *
     * @param int $maxRetryCount Connect funcions stop retrying if retrying count exceeds it. Set 0 to allow infinite retrying count.
     * @param int $timeoutMilliseconds Connect functions stop retrying if trying time exceeds it. Set 0 to allow no timeout.
     * @param int $delayMillisecondsOnRetry means interval time until next retrying of connect functions. Do not set too close to 0.
     */
    function __construct( int $maxRetryCount = 0, int $timeoutMilliseconds = 10000, int $delayMillisecondsOnRetry = 1000 )
    {
        $this->maxRetryCount = $maxRetryCount;
        $this->timeoutMilliseconds = $timeoutMilliseconds;
        $this->delayMillisecondsOnRetry = $delayMillisecondsOnRetry;
    }

    /**
     * @return int
     */
    public function getMaxRetryCount(): int
    {
        return $this->maxRetryCount;
    }

    /**
     * @return int
     */
    public function getTimeoutMilliseconds(): int
    {
        return $this->timeoutMilliseconds;
    }

    /**
     * @return int
     */
    public function getDelayMillisecondsOnRetry(): int
    {
        return $this->delayMillisecondsOnRetry;
    }
}

