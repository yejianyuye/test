<?php
namespace app\web\controller;

use   \think\Request;
use   \think\Db;
use   \app\common\controller\Tpsinfo;
use   \think\Session;
class Tpsappointment extends \think\Controller
{

    //预约主页面
    public function appointment(){
        $dq = Db::table('tps_dxq')->where('parent_id',0)->select();
        return view('appointment', [
            'dq'  => $dq,
        ]);
    }

    //根据大区获取校区
    public function get_dq_children(){
        $data = Request::instance()->get();
        $search['parent_id'] = $data['id'];
        $res = Db::table('tps_dxq')->where($search)->select();
        return $res;
    }

    //根据校区获取预约考点日期
    public function get_rq(){
        $data = Request::instance()->get();
        $res = Db::table('tps_kaodian')->field('ks_rq')->where($data)->select();
        $kk = array();
        if($res){
            $kk = $this->array_unique_fb($res);
        }
        return $kk;
    }

    //根据校区获和日期获取时间
    public function get_sj(){
        $data = Request::instance()->get();
        $res = Db::table('tps_kaodian')->field('ks_data')->where($data)->select();
        $kk = array();
        if($res){
            $kk = $this->array_unique_fb($res);
        }
        return $kk;
    }

    //根据校区获取预约考点信息
    public function get_papertest(){
        $data = Request::instance()->get();
        $res = Db::table('tps_evaluate_paper')
            ->alias('e')
            ->field('e.paper_name,e.id')
            ->join('tps_kaodian t','e.id = t.evaluate_paper_id')
            ->where('t.xq',$data['xq'])
            ->where('t.nianji',$data['nianji'])
            ->where('t.ks_rq',$data['ks_rq'])
            ->where('t.ks_data',$data['ks_data'])
            ->where('e.status',2)
            ->select();
        $kk = array();
        if($res){
           $kk = $this->array_unique_fb($res);
        }
        return $kk;
    }

    //唯一试卷
    function array_unique_fb($array2D){
        foreach ($array2D as $v){
            $v=join(',',$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[]=$v;
        }
        $temp=array_unique($temp); //去掉重复的字符串,也就是重复的一维数组
        $n=0;
        foreach ($temp as $k => $v){
            $ss[$n]=explode(',',$v); //再将拆开的数组重新组装
            $n++;
        }
        return $ss;
    }

    //提交预约
    function to_appointment()
    {

        $data = Request::instance()->get();
        $wz_id = Db::table('tps_kaodian')
            ->alias('k')
            ->field('t.id,t.kd_num')
            ->join('tps_kc_wz t','k.id = t.kd_num')
            ->where('k.ks_rq',$data['rq'])
            ->where('k.ks_data',$data['data'])
            ->where('k.xq',$data['xq'])
            ->where('k.evaluate_paper_id',$data['evaluate_paper_id'])
            ->where('k.nianji',$data['nianji'])
            ->where('t.status',0)
            ->order('t.zuohao  asc')
            ->find();
        if($wz_id){
            $cf = Db::table('tps_kc_wz')->where('tel',$data['tel'])->where('evaluate_paper_id',$data['evaluate_paper_id'])->find();
            if($cf){
                //同一个厂考试  一个手机号码只能预约一次
                $res['res'] = 4;
                return $res;
            }
            //随机生成六位数学号
           $zhunkaonum = $this->getzhunkaonum($data['evaluate_paper_id']);
           $insert_app= array(
               'name'=>$data['student_name'],
               'zhunkaonum'=>$zhunkaonum,
               'tel'=>$data['tel'],
               'status'=>1,
           );
            Db::startTrans();
            $yuyue = Db::table('tps_kc_wz')->where('id',$wz_id['id'])->update($insert_app);
            $yuyue_data = Db::table('tps_kc_wz')->field('zuocihao')->where('id',$wz_id['id'])->find();
            //添加预约信息
            $appointment_data =array(
                    'student_name'=>$data['student_name'],
                    'tel'=>$data['tel'],
                    'student_status'=>$data['student_status'],
                    'studentNo'=>$data['student_num'],
                    'teacher'=>$data['recommend_teacher'],
                    'school'=>$data['school'],
                    'nianji'=>$data['nianji'],
                    'dq'=>$data['dq'],
                    'xq'=>$data['xq'],
                    'evaluate_paper_id'=>$data['evaluate_paper_id'],
                    'kaodian_id'=>$wz_id['id'],
                    'zhunkaonum'=>$zhunkaonum,
                    'zuocihao'=>$yuyue_data['zuocihao'],
            );
            if($yuyue){
                $appointment = Db::table('tps_appointment')->insert($appointment_data);
                if($appointment){
                    Db::commit();
                    $res['student_name'] = $data['student_name'];
                    $res['tel'] = $data['tel'];
                    $res['zhunkaonum'] = $zhunkaonum;
                    $res['res'] = 1;
                    return $res;
                }else{
                    //预约失败
                    Db::rollback();
                    $res['res'] = 2;
                    return $res;
                }
            }else{
                //预约失败
                $res['res'] = 3;
                return $res;
            }

        }else{
            //预约位置已经满了
            $res['res'] = 0;
            return $res;
        }

    }

    //生成准考证号
    public function getzhunkaonum($evaluate_paper_id){
        $zhunkaonum = mt_rand(100000,999999);
        $has_zk = Db::table('tps_kc_wz')->where('evaluate_paper_id',$evaluate_paper_id)->where('zhunkaonum',$zhunkaonum)->find();
        if($has_zk){
           $zhunkaonum = $this->getzhunkaonum($evaluate_paper_id);
           return $zhunkaonum;
        }else{
            return $zhunkaonum;
        }
    }

    //学生考场预约信息查询
    public function studentment(){
        $data = Request::instance()->get();
        $appointment = Db::table('tps_appointment')->field('kaodian_id,student_name,teacher,studentNo,zhunkaonum,tel,school,nianji')->where($data)->find();

        $appointment_info = Db::table('tps_kaodian')
                            ->alias('k')
                            ->field('k.dq_name,k.xq_name,k.kc_name,k.ks_rq,k.ks_data')
                            ->join('tps_kc_wz t','k.id = t.kd_num')
                            ->where('t.id',$appointment['kaodian_id'])
                            ->find();

        return view('studentment', [
            'appointment_info'  => $appointment_info,
            'appointment'  => $appointment,
        ]);
    }

    //考试预约登陆
    public function testpaper_login(){

       // $data = Request::instance()->post();
        //$data = $_GET['id'];

        $data=input('param.');
        $res = 1;
        $get_test_paper_id = Session::get('get_test_paper_id');
        if($get_test_paper_id){

            $appointment_info = Db::table('tps_appointment')->field('status,evaluate_paper_id')->where('evaluate_paper_id', $get_test_paper_id)->find();
            if($appointment_info['evaluate_paper_id'] ==$data['evaluate_paper_id'] && $appointment_info['status'] ==1){
                //该条件为浏览器中存在登陆做题的信息  并且正在做
                $this->redirect('Tps/testpaper', ['evaluate_paper_id' =>$data['evaluate_paper_id'] ]);
            }else if($appointment_info['evaluate_paper_id'] ==$data['evaluate_paper_id'] && $appointment_info['status'] ==2){
                //该条件为浏览器中存在登陆做题的信息  做题已经完成  可以选择重新测试或者是查看检测报告
                $this->redirect('Tps/testpaper', ['evaluate_paper_id' =>$data['evaluate_paper_id'] ]);
                $res = 2;
            }
        }

        //预约登陆 判断是pc端还是wap端浏览器
        if(check_wap()){

            return view('testpaper_login_wap', [
                'evaluate_paper_id'  => $data['evaluate_paper_id'],
                'res'  => $res,
            ]);

        }else{
            return view('testpaper_login', [
                'evaluate_paper_id'  => $data['evaluate_paper_id'],
                'res'  => $res,
            ]);
        }



    }

    //考试预约登陆
    public function do_login_test(){
        $data = Request::instance()->get();
        $res = Db::table('tps_appointment')->where('evaluate_paper_id', $data['evaluate_paper_id'])->where('tel', $data['tel'])->find();

        if($res){
            if($res['student_name'] == $data['student_name'] && $res['status'] != 2){
                //登陆答题 答题尚未完成
                Session::set('get_test_paper_id',$res['id']);
              //  Session::set('tel',$res['tel']);
                //Session::set('tel',$res['tel']);
                return  $res['evaluate_paper_id'];
            }else if($res['student_name'] == $data['student_name'] && $res['status'] == 2){
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