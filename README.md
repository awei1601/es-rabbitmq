## es-rabbitmq使用说明
        本代码目前基于php-amqplib/php-amqplib实现的rabbitmq消息中间件封装类。
    仅仅适用于easyswoole框架下rabbitmq连接操作。

## 环境依赖
```
    PHP SDK 需要依赖 PHP 5及以上
```

## 安装
```bash
$ composer require awei1601/es-rabbitmq
```

#### 在系统配置文件(如配置文件为config.php)中添加一下配置：
```php
return [
    'rabbitmq' => [
        'host' => '***.***.***.***',
        'port' => 5672,
        'user' => '***',
        'password' => '*******',
        'vhost' => '/'
    ],
    // 短信，邮箱，通知队列
    'queue' => [
        'exchange' => 'sms_email',
        'queue' => 'sms_email',
        'routeKey' => '',
        'type' => 'direct'
    ]
];
```

### 示例:
我们创建一个 RabbitMQProcess.php, 消费者进程
```php
<?php
namespace App\Common;

use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\Config;
use EasySwoole\RabbitMq\MqJob;
use EasySwoole\RabbitMq\MqQueue;
use Exception;

class RabbitMQProcess extends AbstractProcess
{
    /**
     * @param $arg
     * @return mixed
     * @throws Exception
     */
    protected function run($arg)
    {
        go(function (){
            MqQueue::getInstance()->refreshConnect()->consumer()->setConfig(
                Config::getInstance()->getConf('queue.exchange'),
                Config::getInstance()->getConf('queue.routeKey'),
                Config::getInstance()->getConf('queue.type'),
                Config::getInstance()->getConf('queue.queue')
            )->listen(function (MqJob $mqJob){
                $res = json_decode($mqJob->getJobData(),true);
                var_dump($res);
            });
        });
    }

}
```

#### EasySwooleEvent.php文件中 mainServerCreate 方法中注册：

```php
use EasySwoole\RabbitMq\RabbitMqQueueDriver;
use EasySwoole\EasySwoole\Config;
use App\Common\MqQueueProcess;
use EasySwoole\EasySwoole\Swoole\EventRegister;

class EasySwooleEvent implements Event
{

    public static function mainServerCreate(EventRegister $register)
    {
         $driver = new RabbitMqQueueDriver(
              Config::getInstance()->getConf('rabbitmq.host'),
              Config::getInstance()->getConf('rabbitmq.port'),
              Config::getInstance()->getConf('rabbitmq.user'),
              Config::getInstance()->getConf('rabbitmq.password'),
              Config::getInstance()->getConf('rabbitmq.vhost')
         );
         MqQueue::getInstance($driver);
         $processConfig= new \EasySwoole\Component\Process\Config();
         $processConfig->setProcessGroup('RabbitMq');//设置进程组
         $processConfig->setRedirectStdinStdout(false);//是否重定向标准io
         $processConfig->setPipeType($processConfig::PIPE_TYPE_SOCK_DGRAM);//设置管道类型
         $processConfig->setEnableCoroutine(true);//是否自动开启协程
         $processConfig->setMaxExitWaitTime(3);//最大退出等待时间
         $processConfig->setProcessName('RabbitMqProcess');
         \EasySwoole\EasySwoole\ServerManager::getInstance()->addProcess(new MqQueueProcess($processConfig));
    }
}
```

#### 消息生成端使用：
```php
use EasySwoole\EasySwoole\Config;
// 处理验证码发送 （消费端）
 class Index extends Controller{
     public function index(){
         $job = new MqJob();
         $job->setJobData('composer hello word'.date('Y-m-d H:i:s', time()));
         MqQueue::getInstance()->producer()->setConfig(
            Config::getInstance()->getConf('queue.exchange'),
            Config::getInstance()->getConf('queue.routeKey'),
            Config::getInstance()->getConf('queue.type'),
            Config::getInstance()->getConf('queue.queue')
         )->push($job);
     }
 }
```