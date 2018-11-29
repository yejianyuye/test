<?php
namespace app\web\controller;

use   \think\Request;
use   \think\Db;
use   \app\common\controller\Tpsinfo;
use   \think\Session;
class Tpsappointment extends \think\Controller
{
    //提交预约信息
    function to_appointment()
    {
        $data = Request::instance()->get();
        $appointment_count = Db::table('tps_paper_appointment')->where('tel ='.$data['tel'].' and evaluate_paper_id ='.$data['evaluate_paper_id'])->count();
        if($appointment_count>0){
            $res['res'] = 2;
            return $res;
        }
        $data['add_time'] = date('Y-m-d H:i:s',time());
        $data['zhunkaonum'] =   $this->getzhunkaonum($data['evaluate_paper_id']);   
        $kkk = Db::table('tps_paper_appointment')->insert($data);
        if($kkk){
            $res['res'] = 1;
            $res['zhunkaonum'] = $data['zhunkaonum'];
            $res['evaluate_paper_id'] = $data['evaluate_paper_id'];
            $res['tel'] = $data['tel'];
            $res['souseid'] = $data['souseid'];
            Session::set('paper'.$res['evaluate_paper_id'],$res['evaluate_paper_id']);
            Session::set('tel'.$res['evaluate_paper_id'],$res['tel']);
        }else{
            $res['res'] =0;
        }
        return $res;
    }

    //生成准考证号
    public function getzhunkaonum($evaluate_paper_id){
        $evaluate_paper_id = $evaluate_paper_id;
        $zhunkaonum = mt_rand(100000,999999);
        $has_zk = Db::table('tps_paper_appointment')->where('zhunkaonum = '.$zhunkaonum.' and evaluate_paper_id = '.$evaluate_paper_id)->count();
        if($has_zk>0){
           $zhunkaonum = $this->getzhunkaonum($evaluate_paper_id);
           return $zhunkaonum;
        }else{
            return $zhunkaonum;
        }
    }


    //学生考场预约信息查询
    public function studentment(){
        $data = Request::instance()->get();
        $appointment_info = Db::table('tps_paper_appointment')->field('student_name,teacher,student_num,zhunkaonum,tel,school')->where($data)->find();
        //判断一份试卷测评是否现在可以继续
        $tpsinfo = New Tpsinfo();
        $appointment_info['cantest'] = $tpsinfo->paper_cantest($data);
        $appointment_info['souseid'] = $data['souseid'];
        $appointment_info['evaluate_paper_id'] = $data['evaluate_paper_id'];
        //var_dump($appointment_info);die;
        return view('studentment', [
            'appointment'  => $appointment_info,
        ]);
    }

    

    //考试预约登陆
    public function testpaper_login(){

        $data=input('param.');
        $appoint_status = Db::table('tps_evaluate_paper')->field('paper_name')->where('add_time ='.$data['souseid'].' and id='.$data['evaluate_paper_id'])->find();
        //预约登陆 判断是pc端还是wap端浏览器
        if(check_wap()){
            return view('testpaper_login_wap', [
                'data'  => $data,
                'paper_name'  => $appoint_status['paper_name'],
            ]);
        }else{
            return view('testpaper_login', [
                'data'  => $data,
                'paper_name'  => $appoint_status['paper_name'],
            ]);
        }
    }


    //考试预约登陆
    public function do_login_test(){
        $data = Request::instance()->get();
        //var_dump($data);
        $res = Db::table('tps_paper_appointment')->field('student_name,evaluate_paper_id,souseid,paper_status,tel')->where('evaluate_paper_id ='. $data['evaluate_paper_id'] . ' and tel ='.$data['tel']. ' and souseid ='.$data['souseid'])->find();
        //var_dump($res);die;

        if($res){
            if($res['student_name'] == $data['student_name'] && $res['paper_status'] == 0){
                //登陆答题 答题尚未完成
                Session::set('paper'.$res['evaluate_paper_id'],$res['evaluate_paper_id']);
                Session::set('tel'.$res['evaluate_paper_id'],$res['tel']);
                return  $res;
            }else if($res['student_name'] == $data['student_name'] && $res['paper_status'] == 1){
                //登陆答题答题已经完成
                return  -2;
            }else{
                //用户名或者密码不正确
                return  0;
            }
        }else{
            return  -1;
        }
    }



}