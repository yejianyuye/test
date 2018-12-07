<?php
namespace app\web\controller;

use   \think\Request;
use   \think\Db;
use   \think\Session;
/*
*根据大区分为预约id和非预约id
*
*
*
*/
class Tps extends \think\Controller
{

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

    /*public function _initialize(){
        //检测是否登录
        $student_num= Session::get('student_num');
        $tel= Session::get('tel');
        if($student_num && $tel){
            $count = Db::table('tps_students')->where('tel ='.$tel.' and student_num = '.$student_num)->count();
            if($count == 1){
                $this->redirect('Tpsstudentinfo/student_index');
            }
        }
    }*/
    

}