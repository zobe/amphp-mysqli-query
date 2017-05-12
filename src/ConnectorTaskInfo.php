<?php

namespace zobe\AmphpMysqliQuery;

use zobe\TaskInfo\CancelableTaskInfoInterface;
use zobe\TaskInfo\CancelableTaskInfoTrait;
use zobe\TaskInfo\LifeTimeTaskInfoInterface;
use zobe\TaskInfo\LifeTimeTaskInfoTrait;
use zobe\TaskInfo\MysqliTaskInfoInterface;
use zobe\TaskInfo\MysqliTaskInfoTrait;
use zobe\TaskInfo\TaskInfo;

class ConnectorTaskInfo extends TaskInfo implements MysqliTaskInfoInterface, CancelableTaskInfoInterface, LifeTimeTaskInfoInterface
{
    use MysqliTaskInfoTrait;
    use CancelableTaskInfoTrait;
    use LifeTimeTaskInfoTrait;

    /**
     * @var int
     */
    protected $retryCount = 0;

    /**
     * Same as mysqli->connect_errno of the last retry
     *
     * @see http://php.net/manual/en/mysqli.connect-errno.php
     * @return string
     */
    public function getErrorNo(): string
    {
        $mysqli = $this->getMysqli();
        if( !is_null($mysqli) )
            return $mysqli->connect_errno;
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
        $mysqli = $this->getMysqli();
        if( !is_null($mysqli) )
            return $mysqli->connect_error;
        return '';
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
}


