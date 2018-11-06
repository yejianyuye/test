<?php
namespace app\index\controller;
use   \think\Request;
use   \think\Db;
use   \think\Session;

class Cologin extends \think\Controller
{

    private $xsl_adminid;
    private $status;

    //校验登陆
    public function _initialize(){
        //检测是否登录
        $xsl_adminid= Session::get('tps_adminid');

        if (!$xsl_adminid)
        {
            $this->error(('请先登录！！！'), url('/index/Login/logins'));
        }else{
            $res = Db::table('user')->where('id', $xsl_adminid)->find();
            if($res['username'] != Session::get('admin_name') || $res['password'] != Session::get('password') ){
                $this->error(('请先登录！！！'), url('/index/Login/logins'));
            }
        }

         $this->xsl_adminid = $xsl_adminid;
         $this->status =Session::get('status');

    }





}