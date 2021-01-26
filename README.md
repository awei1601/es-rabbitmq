## es-rabbitmq使用说明
    本代码目前仅仅适用于easyswoole框架下rabbitmq连接池操作封装。
## 环境依赖
```
    PHP SDK 需要依赖 PHP 5及以上
```
#### 在系统配置文件(如配置文件为config.php)中添加一下配置：
```php
    'rabbitmq' => [
        'host' => '***.***.***.***',
        'port' => 5672,
        'user' => '***',
        'passwd' => '***',
        'vhost' => '/'
    ],
    // 邮件短信验证码消息队列配置
    'sms_email' => [
        'exchange' => '****',
        'queue' => '****',
        'routeKey' => '',
        'type' => 'direct',
        'PoolName' => '*****'
    ],
```
#### EasySwooleEvent.php文件中initialize方法中注册：
```php
class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');
        // rabbitMQ连接池
        $rabMQPoolConfig = RabbitMQ::getInstance()->register(Config::getInstance()->getConf('sms_email.PoolName'),new RabbitMQConfig(Config::getInstance()->getConf('rabbitmq')));
        $rabMQPoolConfig->setMinObjectNum(1);
        $rabMQPoolConfig->setMaxObjectNum(2);
        $rabMQPoolConfig->setIntervalCheckTime(30);
        $rabMQPoolConfig->setMaxIdleTime(30000000);
        $rabMQChannelPoolConfig = RabbitMQChannel::getInstance()->register('rabbitMq_channel_pool',new RabbitMQChannelConfig(Config::getInstance()->getConf('sms_email')));
        $rabMQChannelPoolConfig->setMaxIdleTime(3000000);
    }
```
#### 消费端使用：
```php
    // 处理验证码发送 （消费端）
    go(function (){
        RabbitMQChannel::invoke('rabbitMq_channel_pool',function (AMQPChannel $channel){
            $channel->basic_consume(
                Config::getInstance()->getConf('sms_email.sms_email'),
                'consumer',false,false,false,false,
                function (AMQPMessage $message){ // 回调方法
                    var_dump($message->body);
                }
            );
            while (count($channel->callbacks)){
                $channel->wait();
            }
        });
    });
```