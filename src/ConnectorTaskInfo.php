<?php

namespace zobe\AmphpMysqliQuery;

use zobe\TaskInfo\CancelableTaskInfoInterface;
use zobe\TaskInfo\CancelableTaskInfoTrait;
use zobe\TaskInfo\ExceptionSuppressedTaskInfoInterface;
use zobe\TaskInfo\ExceptionSuppressedTaskInfoTrait;
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
    public function setRetryCount_ConnectorTaskInfo(int $retryCount)
    {
        $this->retryCount = $retryCount;
    }
}

class ExceptionSuppressedConnectorTaskInfo extends ConnectorTaskInfo implements ExceptionSuppressedTaskInfoInterface
{
    use ExceptionSuppressedTaskInfoTrait;
}

class ConnectorTaskInfoFactory
{
    /**
     * Do not use
     * @param \mysqli|null $mysqli
     * @param float $startTime
     * @param int $retryCount
     * @param string|null $title
     * @param string|null $description
     * @return ConnectorTaskInfo
     */
    public static function create( $mysqli, float $startTime, int $retryCount, string $title = null, string $description = null )
    {
        if( is_null($title) )
            $title = 'ConnectorTaskInfo';
        if( is_null($description) )
            $description = 'ConnectorTaskInfo: ' .
                'StartTime: ' . $startTime .
                ', RetryCount: ' . $retryCount;

        $a = new ConnectorTaskInfo();
        $a->setTitle_TitleAndDescriptionTaskInfoTrait( $title );
        $a->setDescription_TitleAndDescriptionTaskInfoTrait( $description );
        if( $mysqli instanceof \mysqli )
            $a->setMysqli_MysqliTaskInfoTrait($mysqli);
        $a->setStartTime_LifeTimeTaskInfoTrait($startTime);
        $a->setRetryCount_ConnectorTaskInfo($retryCount);
        return $a;
    }

    /**
     * Do not use
     * @param \mysqli|null $mysqli
     * @param float $startTime
     * @param int $retryCount
     * @param \Throwable $e
     * @param string|null $title
     * @param string|null $description
     * @return ExceptionSuppressedConnectorTaskInfo
     */
    public static function createExceptionSuppressed( $mysqli, float $startTime, int $retryCount, \Throwable $e, string $title = null, string $description = null )
    {
        if( is_null($title) )
            $title = 'ExceptionSuppressedConnectorTaskInfo: ' . 'Code: ' . $e->getCode() . ', Msg: ' . $e->getMessage();
        if( is_null($description) )
            $description = 'ExceptionSuppressedConnectorTaskInfo: ' .
                'Code: ' . $e->getCode() .
                ', Msg: ' . $e->getMessage() .
                ', StartTime: ' . $startTime .
                ', RetryCount: ' . $retryCount;

        $a = new ExceptionSuppressedConnectorTaskInfo();
        $a->setTitle_TitleAndDescriptionTaskInfoTrait( $title );
        $a->setDescription_TitleAndDescriptionTaskInfoTrait( $description );
        if( $mysqli instanceof \mysqli )
            $a->setMysqli_MysqliTaskInfoTrait($mysqli);
        $a->setStartTime_LifeTimeTaskInfoTrait($startTime);
        $a->setRetryCount_ConnectorTaskInfo($retryCount);
        $a->setSuppressedException_ExceptionSuppressedTaskInfoTrait($e);
        return $a;
    }
}


