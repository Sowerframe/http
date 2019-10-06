<?php
#coding: utf-8
# +-------------------------------------------------------------------
# | Worker
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\worker;

use sower\Service as BaseService;

class Service extends BaseService
{
    public function register()
    {
        $this->commands([
            'worker'         => '\\sower\\worker\\command\\Worker',
            'worker:server'  => '\\sower\\worker\\command\\Server',
            'worker:gateway' => '\\sower\\worker\\command\\GatewayWorker',
        ]);
    }
}
