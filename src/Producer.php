<?php

namespace EasySwoole\RabbitMq;

use Exception;
use Swoole\Atomic\Long;

class Producer
{
    private Long $atomic;
    private RabbitMqQueueDriver $driver;
    private ?string $nodeId;
    private string $exchange = '';
    private string $routingKey = '';
    private string $mqType = 'direct';
    private string $queueName = '';
    private bool $writeExchange = false;

    function __construct(RabbitMqQueueDriver $driver, Long $atomic, ?string $nodeId = null)
    {
        $this->atomic = $atomic;
        $this->driver = $driver;
        $this->nodeId = $nodeId;
    }

    /**
     * 初始化监听队列名
     * @param $exchange //交换器名称
     * @param $routingKey  //绑定路由和队列名称
     * @param $mqType  //交换器类型
     * @param $queueName  //队列名称
     * @return $this
     */
    public function setConfig($exchange, $routingKey ,$mqType = 'direct' ,$queueName = ''): Producer
    {
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->mqType = $mqType;
        $this->queueName = $queueName;
        $this->writeExchange = true;
        return $this;
    }

    /**
     * @param MqJob $job
     * @param bool $init
     * @return int
     * @throws Exception
     */
    function push(MqJob $job, bool $init = true): int
    {
        if ($this->writeExchange) {
            $job->setExchange($this->exchange);
            $job->setRoutingKey($this->routingKey);
            $job->setMqType($this->mqType);
            $job->setQueueName($this->queueName);
            $this->writeExchange = false;
        }
        $id = $this->atomic->add(1);
        if ($id > 0) {
            if ($init) {
                $job->setJobId($id);
                $job->setNodeId($this->nodeId);
            }
            try {
                $ret = $this->driver->push($job);
                if ($ret) {
                    return $id;
                }
            } catch (Exception $e) {
                throw $e;
            }
        }
        return 0;
    }
}