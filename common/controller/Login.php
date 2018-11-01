<?php

namespace app\common\controller;

use think\Session;
/**
 * 后台控制器基类
 */
class Login extends \think\Controller
{

    public function _initialize()
    {
            //检测是否登录


                $admin = Session::get('admin');
                if (!$admin)
                {
                    $this->error(('Please login first'), url('/index/index/login'));
                }
    }




}
