<?php
#coding: utf-8
# +-------------------------------------------------------------------
# | Workerman Cookie类
# +-------------------------------------------------------------------
# | Copyright (c) 2017-2019 Sower rights reserved.
# +-------------------------------------------------------------------
# +-------------------------------------------------------------------
namespace sower\worker;
use sower\Cookie as BaseCookie;
use Workerman\Protocols\Http as WorkerHttp;
class Cookie extends BaseCookie
{
    /**
     * 保存Cookie
     * @access public
     * @param  string $name cookie名称
     * @param  string $value cookie值
     * @param  int    $expire cookie过期时间
     * @param  string $path 有效的服务器路径
     * @param  string $domain 有效域名/子域名
     * @param  bool   $secure 是否仅仅通过HTTPS
     * @param  bool   $httponly 仅可通过HTTP访问
     * @return void
     */
    protected function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly): void
    {
        WorkerHttp::setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

}
