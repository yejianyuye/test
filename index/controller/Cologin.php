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
        $tps_adminid= Session::get('tps_adminid');

        if (!$tps_adminid){
            $this->error(('请先登录！！！'), url('/index/Login/login'));
        }else{
            $res = Db::table('tps_user')->where('id',$tps_adminid)->find();
            if($res['zu_id'] != Session::get('tps_zuid') ){
                $this->error(('请先登录！！！'), url('/index/Login/login'));
            }
        }
         //$this->tps_adminid = $tps_adminid;
         //$this->status =Session::get('status');
    }

    

}