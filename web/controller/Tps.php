<?php
namespace app\web\controller;

use   \think\Request;
use   \think\Db;
use   \app\common\controller\Tpsinfo;
use   \think\Session;
/*
*根据大区分为预约id和非预约id
*
*
*
*/
class Tps extends \think\Controller
{


    private $is_pc;
    //校验登陆
    public function _initialize(){
        //检测是否登录
        $this->is_pc= check_wap();
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


    //考试试卷
    public function testpaper(){
        //预约id
        $data = Request::instance()->get();
        //判断试卷参数是否正确
        if(!array_key_exists('evaluate_paper_id',$data)  || $data['evaluate_paper_id']== ''){
            return view('common/wap_no_paper', [
                        'firsr_one'  => '没有试卷',
                        'firsr_two'=>'试卷已去远方',
                    ]);
         }
        
         //判断试卷时间限制
         $tpsinfo = New Tpsinfo();
         $can_test = $tpsinfo->paper_appointment($data);
         if($can_test ==2){
            return view('common/wap_no_paper', [
                        'firsr_one'  => '试卷已过期',
                        'firsr_two'=>'试卷已去远方',
                    ]);
         }
         //判断是否需要预约
        $appoint_status = Db::table('tps_evaluate_paper')->field('is_appointment,paper_name,cp_sc,cp_time,add_time')->where('add_time ='.$data['souseid'].' and id='.$data['evaluate_paper_id'])->find();

         if($appoint_status==''){
            return view('common/wap_no_paper', [
                        'firsr_one'  => '没有试卷',
                        'firsr_two'=>'试卷已去远方',
                    ]);
         }
         //是否需要预约
         if($appoint_status['is_appointment'] == 1){

            $paper = Session::get('paper'.$data['evaluate_paper_id']);
            $tel = Session::get('tel'.$data['evaluate_paper_id']);

            if($paper && $tel){
                $paper_appointment = Db::table('tps_paper_appointment')->field('paper_status')->where('evaluate_paper_id ='.$paper.' and tel ='.$tel)->find();

                if($paper_appointment['paper_status'] == 1){
                    return view('common/wap_no_paper', [
                        'firsr_one'  => '您完成此预约的测评',
                        'firsr_two'=>'试卷已去远方',
                    ]);
                }
            }else{

                return view('Tpsappointment/appointment', [
                    'appointment_data'  => $data,
                    'paper_name'=> $appoint_status['paper_name'],
                ]);
            }   
        }

        //根据试卷判断是否可以开始测评
        $cantest = $tpsinfo->paper_cantest($data);
        if($cantest==2){
            return view('common/wap_no_paper', [
                        'firsr_one'  => '试卷已过期',
                        'firsr_two'=>'试卷已去远方',
                    ]);
        } 
        //手机端页面展示
        if($this->is_pc){

            $tpsinfo = New Tpsinfo();
            $paper_question_all = $tpsinfo->get_paper_wap($data['evaluate_paper_id'],1);
            return view('testpaper/wappaper', [
                'id'  => $data['evaluate_paper_id'],
                'tel' => '15629941330',
                'souseid'=>$appoint_status['add_time'],
                'evaluate_paper_id'  =>$data['evaluate_paper_id'],
                'paper_question'  => $paper_question_all['paper_ss'],
                'num'  => $paper_question_all['num'],
                'time_allow'  => $appoint_status['cp_sc'],
                'paper_name'  => $appoint_status['paper_name'],
                'cp_time'  => $appoint_status['cp_time']*60,
            ]);

        //pc端页面展示    
        }else{
            //$search_data['evaluate_paper_id'] = $data['evaluate_paper_id'];
            $paper_question_all = $tpsinfo->get_paper($data);
            return view('testpaper/pcpaper', [
                'id'  => $data['evaluate_paper_id'],
                'tel' => '15629941330',
                'souseid'=>$appoint_status['add_time'],
                'paper_question'  => $paper_question_all['paper_question'],
                'count'  => $paper_question_all['count'],
                'pageall'  => $paper_question_all['pageall'],
                'time_allow'  => $appoint_status['cp_sc'],
                'paper_name'  => $appoint_status['paper_name'],
                'cp_time'  => $appoint_status['cp_time']*60,
                'evaluate_paper_id'  => $data['evaluate_paper_id'],
                
            ]);
        }      
    }


                

    

}