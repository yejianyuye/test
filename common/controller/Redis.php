<?php
namespace app\common\controller;
use  think\Db;
use think\Controller;
header("Content-Type:text/html;charset=utf8");

class Redis {


    private $redis;
    private $config;
    private $namespace;
    private $session_id = null;
    private $maxlifetime = null;


    public function __construct($config) {
        //初始化redis
        $this->config = array(
            'namespace'=>'',
            'host'=>'127.0.0.1',
            'port'=>'6789',
            'auth'=>'',
            'db'=>0
        );
        $this->config = array_merge($this->config,$config);
        $this->redis = new \Redis();
        $this->redis->connect($this->config['host'],$this->config['port']);
        if(!empty($this->config['auth']))
        {
            $ret = $this->redis->auth($this->config['auth']);
            if(!$ret)
            {
                return false;
            }
            $this->redis->select($this->config['db']);
        }
        //设置当前的sessionid生成的命名空间
        $this->namespace = $this->config['namespace'];
        if(!empty($this->namespace))
        {
            $this->namespace .=':';
        }
        //设置初始化的session_id
        if(empty($session_name))
        {
            $session_name = ini_get("session.name");
        }
        //没有设置过session，新的会话，需要重新生成sessionid
        if ( empty($_COOKIE[$session_name]) ) {
            $this->session_id = true;
        }


    }


}