<?php
#coding: utf-8
# +-------------------------------------------------------------------
# | 数据集管理类
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\worker\command;

use sower\console\Command;
use sower\console\Input;
use sower\console\input\Argument;
use sower\console\input\Option;
use sower\console\Output;
use sower\facade\App;
use sower\facade\Config;
use sower\worker\Server as WorkerServer;
use Workerman\Worker;

/**
 * Worker Server 命令行类
 */
class Server extends Command
{
    protected $config = [];

    public function configure()
    {
        $this->setName('worker:server')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'the host of workerman server.', null)
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'the port of workerman server.', null)
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
            ->setDescription('Workerman Server for Sower');
    }

    public function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');

        if (DIRECTORY_SEPARATOR !== '\\') {
            if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
                $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .</error>");
                return false;
            }

            global $argv;
            array_shift($argv);
            array_shift($argv);
            array_unshift($argv, 'sower', $action);
        } elseif ('start' != $action) {
            $output->writeln("<error>Not Support action:{$action} on Windows.</error>");
            return false;
        }

        $this->config = Config::get('worker_server');

        if ('start' == $action) {
            $output->writeln('Starting Workerman server...');
        }

        // 自定义服务器入口类
        if (!empty($this->config['worker_class'])) {
            $class = (array) $this->config['worker_class'];

            foreach ($class as $server) {
                $this->startServer($server);
            }

            // Run worker
            Worker::runAll();
            return;
        }

        if (!empty($this->config['socket'])) {
            $socket            = $this->config['socket'];
            list($host, $port) = explode(':', $socket);
        } else {
            $host     = $this->getHost();
            $port     = $this->getPort();
            $protocol = !empty($this->config['protocol']) ? $this->config['protocol'] : 'websocket';
            $socket   = $protocol . '://' . $host . ':' . $port;
            unset($this->config['host'], $this->config['port'], $this->config['protocol']);
        }

        if (isset($this->config['context'])) {
            $context = $this->config['context'];
            unset($this->config['context']);
        } else {
            $context = [];
        }

        $worker = new Worker($socket, $context);

        if (empty($this->config['pidFile'])) {
            $this->config['pidFile'] = App::getRootPath() . 'runtime/worker.pid';
        }

        // 避免pid混乱
        $this->config['pidFile'] .= '_' . $port;

        // 开启守护进程模式
        if ($this->input->hasOption('daemon')) {
            Worker::$daemonize = true;
        }

        if (!empty($this->config['ssl'])) {
            $this->config['transport'] = 'ssl';
            unset($this->config['ssl']);
        }

        // 设置服务器参数
        foreach ($this->config as $name => $val) {
            if (in_array($name, ['stdoutFile', 'daemonize', 'pidFile', 'logFile'])) {
                Worker::${$name} = $val;
            } else {
                $worker->$name = $val;
            }
        }

        // Run worker
        Worker::runAll();
    }

    protected function startServer(string $class)
    {
        if (class_exists($class)) {
            $worker = new $class;
            if (!$worker instanceof WorkerServer) {
                $this->output->writeln("<error>Worker Server Class Must extends \\sower\\worker\\Server</error>");
            }
        } else {
            $this->output->writeln("<error>Worker Server Class Not Exists : {$class}</error>");
        }
    }

    protected function getHost()
    {
        if ($this->input->hasOption('host')) {
            $host = $this->input->getOption('host');
        } else {
            $host = !empty($this->config['host']) ? $this->config['host'] : '0.0.0.0';
        }

        return $host;
    }

    protected function getPort()
    {
        if ($this->input->hasOption('port')) {
            $port = $this->input->getOption('port');
        } else {
            $port = !empty($this->config['port']) ? $this->config['port'] : 2345;
        }

        return $port;
    }
}
