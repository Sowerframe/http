<?php
#coding: utf-8
# +-------------------------------------------------------------------
# | Worker控制器扩展类
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\worker;
use Workerman\Worker;
abstract class Server
{
    protected $worker;
    protected $socket   = '';
    protected $protocol = 'http';
    protected $host     = '0.0.0.0';
    protected $port     = '2346';
    protected $option   = [];
    protected $context  = [];
    protected $event    = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'];

    /**
     * 架构函数
     * @access public
     */
    public function __construct()
    {
        // 实例化 Websocket 服务
        $this->worker = new Worker($this->socket ?: $this->protocol . '://' . $this->host . ':' . $this->port, $this->context);

        // 设置参数
        if (!empty($this->option)) {
            foreach ($this->option as $key => $val) {
                $this->worker->$key = $val;
            }
        }

        // 设置回调
        foreach ($this->event as $event) {
            if (method_exists($this, $event)) {
                $this->worker->$event = [$this, $event];
            }
        }

        // 初始化
        $this->init();
    }

    protected function init()
    {
    }

    public function start()
    {
        Worker::runAll();
    }

    public function __set($name, $value)
    {
        $this->worker->$name = $value;
    }

    public function __call($method, $args)
    {
        call_user_func_array([$this->worker, $method], $args);
    }
}
