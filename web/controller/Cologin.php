<?php
namespace app\web\controller;
use   \think\Request;
use   \think\Db;
use   \think\Session;

class Cologin extends \think\Controller
{

    //校验登陆
    public function _initialize(){
        //检测是否登录
        $tel= Session::get('tel');
        //var_dump(Session::get('tel'));
       // var_dump(Session::get('student_num'));die;
        //获取来源
        //echo $_SERVER['HTTP_REFERER'];
        //获取
        $return_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        Session::set('return_url','');
        if (!$tel){
            Session::set('return_url',$return_url);
            $this->redirect('Tpsstudent/student_login');
        }else{
            $res = Db::table('tps_students')->where('tel',$tel)->find();

            if($res['student_num'] != Session::get('student_num') ){
                
                Session::set('return_url',$return_url);
                $this->redirect('Tpsstudent/student_login');
            }
        }
    }



}