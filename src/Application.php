<?php
#coding: utf-8
# +-------------------------------------------------------------------
# | Worker应用对象
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\worker;

use sower\App;
use sower\exception\Handle;
use sower\exception\HttpException;
use Workerman\Protocols\Http as WorkerHttp;
class Application extends App
{
    /**
     * 处理Worker请求
     * @access public
     * @param  \Workerman\Connection\TcpConnection   $connection
     * @param  void
     */
    public function worker($connection)
    {
        try {
            $this->beginTime = microtime(true);
            $this->beginMem  = memory_get_usage();
            $this->db->clearQueryTimes();

            $pathinfo = ltrim(strpos($_SERVER['REQUEST_URI'], '?') ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'], '/');

            $this->request
                ->setPathinfo($pathinfo)
                ->withInput($GLOBALS['HTTP_RAW_REQUEST_DATA']);

            while (ob_get_level() > 1) {
                ob_end_clean();
            }

            ob_start();
            $response = $this->http->run();
            $content  = ob_get_clean();

            ob_start();

            $response->send();
            $this->http->end($response);

            $content .= ob_get_clean() ?: '';

            $this->httpResponseCode($response->getCode());

            foreach ($response->getHeader() as $name => $val) {
                // 发送头部信息
                WorkerHttp::header($name . (!is_null($val) ? ':' . $val : ''));
            }

            $connection->send($content);
        } catch (HttpException | \Exception | \Throwable $e) {
            $this->exception($connection, $e);
        }
    }

    /**
     * 是否运行在命令行下
     * @return bool
     */
    public function runningInConsole()
    {
        return false;
    }

    protected function httpResponseCode($code = 200)
    {
        WorkerHttp::header('HTTP/1.1', true, $code);
    }

    protected function exception($connection, $e)
    {
        if ($e instanceof \Exception) {
            $handler = $this->make(Handle::class);
            $handler->report($e);

            $resp    = $handler->render($this->request, $e);
            $content = $resp->getContent();
            $code    = $resp->getCode();

            $this->httpResponseCode($code);
            $connection->send($content);
        } else {
            $this->httpResponseCode(500);
            $connection->send($e->getMessage());
        }
    }

}
