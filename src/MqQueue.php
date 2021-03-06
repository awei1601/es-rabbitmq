<?php

namespace EasySwoole\RabbitMq;

use EasySwoole\Component\Singleton;
use EasySwoole\Utility\Random;
use Swoole\Atomic\Long;

class MqQueue
{
    use Singleton;
    /**
     * @var RabbitMqQueueDriver $driver
     */
    private RabbitMqQueueDriver $driver;
    private Long $atomic;
    private $nodeId;

    function __construct(RabbitMqQueueDriver $driver)
    {
        $this->driver = $driver;
        $this->atomic = new Long(0);
        $this->nodeId = Random::character(6);
    }

    /**
     * 刷新重建一个链接
     * @return MqQueue
     */
    function refreshConnect(): MqQueue
    {
        return new static($this->driver->refreshConnect());
    }

    /**
     * @return RabbitMqQueueDriver
     */
    function queueDriver(): RabbitMqQueueDriver
    {
        return $this->driver;
    }

    function producer(): Producer
    {
        return new Producer($this->driver, $this->atomic, $this->nodeId);
    }

    function consumer(): Consumer
    {
        return new Consumer($this->driver);
    }


    function size(): ?int
    {
        return $this->driver->size();
    }

    function currentJobId(): int
    {
        return $this->atomic->get();
    }

    function setJobStartId(int $id): MqQueue
    {
        $this->atomic->set($id);
        return $this;
    }

    /**
     * @return bool|string
     */
    public function getNodeId()
    {
        return $this->nodeId;
    }

    /**
     * @param bool|string $nodeId
     */
    public function setNodeId($nodeId): void
    {
        $this->nodeId = $nodeId;
    }
}