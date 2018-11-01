<?php
namespace app\web\controller;
/*电脑端测评报告*/
use   \think\Request;
use   \think\Db;

class Pcreport extends \think\Controller
{

    //学生测评报告
    public function report(){

        $data = Request::instance()->get();
        $appointment_id = $data['appointment_id'];
        $student_report  = Db::table('tps_student_report')
            ->alias('s')
            ->field('s.all_score,s.dengji,s.point_id,s.point_dengji,s.point_name,s.point_bz,s.point_zq,s.point_num,s.question_all,a.evaluate_paper_id,a.student_name,d.res_desc,d.zongji,d.tuijian')
            ->join('tps_appointment a','a.id = s.appointment_id')
            ->join('tps_report_dis d','d.evaluate_paper_id = a.evaluate_paper_id')
            ->where('s.appointment_id = '.$appointment_id)
            ->where('d.type = s.dengji')
            ->find();

        $point_id = explode(',',$student_report['point_id']);
        $point_dengji = explode(',',$student_report['point_dengji']);
        $point_name = explode(',',$student_report['point_name']);
        $point_bz = explode(',',$student_report['point_bz']);
        $point_zq = explode(',',$student_report['point_zq']);
        $point_num = explode(',',$student_report['point_num']);
        $question_all = explode(',',$student_report['question_all']);
        $point_array = array();
        foreach($point_id as $pk=>$pv){
            $point_array[$pk]['point_id'] =  $point_id[$pk];
            $point_array[$pk]['point_dengji'] =  $point_dengji[$pk];
            $point_array[$pk]['dx_desc'] =  Db::table('tps_report_dis')->where('evaluate_paper_id ='.$student_report['evaluate_paper_id'].' and dx_type = '.$point_array[$pk]['point_dengji'])->value('dx_desc');
            $point_array[$pk]['point_name'] =  $point_name[$pk];
            $point_array[$pk]['point_bz'] =  $point_bz[$pk]*100;
            $point_array[$pk]['point_zq'] =  $point_zq[$pk]*100;
            $point_array[$pk]['point_num'] =  $point_num[$pk];
            $point_array[$pk]['question_all'] =  $question_all[$pk];
        }

        return view('Tpsreport/report', [
            'student_report'  => $student_report,
            'point_array'  => $point_array,
            'appointment_id'  => $appointment_id,
        ]);
    }

    //每个考点下面  题目的真确性
    public function report_answer_detail(){
        $data = Request::instance()->get();
        $appointment_id = $data['appointment_id'];
        $student_report  = Db::table('tps_appointment')
            ->alias('a')
            ->field('p.question_all,p.id,p.point_title')
            // ->field('s.all_score,s.dengji,s.point_id,s.point_dengji,s.point_name,s.point_bz,s.point_zq,s.point_num,s.question_all,a.evaluate_paper_id,a.student_name,d.res_desc,d.zongji,d.tuijian')
            ->join('tps_paper_point p','p.evaluate_paper_id = a.evaluate_paper_id')
            ->where('a.id = '.$appointment_id)
            ->select();
        $zq = 0;
        $grade = 0;
        foreach($student_report as $sk=>$sv){
            $student_report[$sk]['question_all'] = $student_report[$sk]['question_all'] + 1;
            for($n=1;$n<$student_report[$sk]['question_all'];$n++){
                $res = Db::table('tps_student_answer')->field('grade')->where('question_id = '.$n.' and isok=1 and point_id ='.$student_report[$sk]['id'])->find();
                if($res){
                    $student_report[$sk]['num_pd'][$n] = 1;
                    $zq++;
                    $grade = $grade + $res['grade'];
                }else{
                    $student_report[$sk]['num_pd'][$n] = 0;
                }

            }
        }
        return view('report_answer_detail', [
            'student_report'  => $student_report,
            'zq'  => $zq,
            'grade'  => $grade,
        ]);
    }
}