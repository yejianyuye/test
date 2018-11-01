<?php
namespace app\web\controller;
/*手机端端测评报告  已预约id为基本导向*/
use   \think\Request;
use   \think\Db;
use   \app\common\controller\Tpsinfo;

class Wapreport extends \think\Controller
{
    //学生测评报告
    public function report(){
        $data = Request::instance()->get();
        $appointment_id = $data['appointment_id'];
      //  $appointment_id = 18;
        $student_report  = Db::table('tps_student_report')
            ->alias('s')
            ->field('s.all_score,s.create_time,s.use_time_ms,s.dengji,s.point_id,s.point_dengji,s.point_name,s.point_bz,s.point_zq,s.point_num,s.question_all,a.evaluate_paper_id,a.student_name,d.res_desc,d.zongji,d.tuijian,tep.paper_name,tep.grade')
            ->join('tps_appointment a','a.id = s.appointment_id')
            ->join('tps_report_dis d','d.evaluate_paper_id = a.evaluate_paper_id')
            ->join('tps_evaluate_paper tep','tep.id = a.evaluate_paper_id')
            ->where('s.appointment_id = '.$appointment_id)
            ->where('d.type = s.dengji')
            ->find();
       // var_dump($student_report);die;
        if($student_report == null){

            echo '报告暂时没有生成';die;

        }else{
            //正确率
            $student_report['zql'] = round(($student_report['all_score']/$student_report['grade'])*100,2);
            //获取时间
            $time = explode(' ',$student_report['create_time']);
            $y_time = explode('-',$time[0]);
            $h_time = explode(':',$time[1]);
            //2018年03月22日 11时15分
            $student_report['start_time'] = $y_time[0].'年'.$y_time[1].'月'.$y_time[2].'日 '.$h_time[0].'时'.$h_time[1].'分';
            //判断是否开启推荐
            $tuijian_isok = Db::table('tps_evaluate_paper')->field('tuijian_kc')->where('id ='.$student_report['evaluate_paper_id'])->find();
            if($tuijian_isok['tuijian_kc'] == 1){
                $allscore = $student_report['all_score'];
                $tuijian_isoks= Db::table('tps_evaluate_tuijian')->where('evaluate_paper_id ='.$student_report['evaluate_paper_id'].' and minScore <= '.$allscore.' and maxScore >= '.$allscore)->find();
                $tj_kc = explode(';',$tuijian_isoks['tj_kc']);
                $tj_kcdz = explode(';',$tuijian_isoks['tj_kcdz']);
                $tuijian = array();
                foreach($tj_kc as $tk=>$tv){
                    $tuijian[$tk]['tj_kc'] =  $tj_kc[$tk];
                    $tuijian[$tk]['tj_kcdz'] =  $tj_kcdz[$tk];
                }
            }
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
            return view('web_tpsreport', [
                'student_report'  => $student_report,
                'point_array'  => $point_array,
                'appointment_id'  => $appointment_id,
                'appointment_id'  => $appointment_id,
                'tuijian_isok'  => $tuijian_isok['tuijian_kc'],
                'tuijian'  => $tuijian,
            ]);
        }

    }

    //每个考点下面  题目的真确性
    public function report_answer_detail(){
        $data = Request::instance()->get();

       // $appointment_id = $data['appointment_id'];
        $appointment_id = 18;
        $student_report  = Db::table('tps_appointment')
            ->alias('a')
            ->field('p.question_all,p.id,p.point_title')
            ->join('tps_paper_point p','p.evaluate_paper_id = a.evaluate_paper_id')
            ->where('a.id = '.$appointment_id)
            ->order('p.point_order asc')
            ->select();

        //获取考试时间
        $time_use  = Db::table('tps_student_report')->field('use_time_ms,use_time')->where('appointment_id ='.$appointment_id)->find();

        $theTime0 = 0;// 秒
        $theTime1 = 0;// 分
        if($time_use['use_time'] > 60) {
            $theTime1 = floor($time_use['use_time']/60);
            $theTime0 = $time_use['use_time']%60;
        }else{
            $theTime0 = $time_use['use_time'];
        }

        if( $theTime1<10){
            $theTime1 = '0'.$theTime1;
        }
        if( $theTime0<10){
            $theTime0 = '0'.$theTime0;
        }
        $time_use['use_time'] = $theTime1.':'.$theTime0;

        //暂时以选择题为主
        $report_question = array();
        //总共题数  答题数  答题正确数
        $report_ren = array();
        foreach($student_report as $sk=>$sv){
           //$report_question[$sk]['num_pd'] = Db::table('tps_student_answer')->where('appointment_id = '.$appointment_id.' and point_id ='.$student_report[$sk]['id'])->select();

            $report_question[$sk]['num_pd'] = Db::table('tps_student_answer')
                                            ->alias('tsa')
                                            ->field('tsa.isok,tqp.question_page,tqp.question_number,tqp.question_type')
                                            ->join('tps_paper_question tqp','tqp.id= tsa.paper_question_id')
                                            ->where('tsa.appointment_id ='.$appointment_id.' and  tsa.point_id ='.$student_report[$sk]['id'])
                                            ->order('tsa.question_id asc')
                                            ->select();

            $report_question[$sk]['point_title'] = $student_report[$sk]['point_title'] ;
        }
        //总题目数
        $report_ren['allcount'] = Db::table('tps_student_answer')->where('appointment_id = '.$appointment_id)->count();
        //正确数
        $report_ren['zqcount'] = Db::table('tps_student_answer')->where('appointment_id = '.$appointment_id.' and isok =2')->count();
        //没做的题目数
        $report_ren['mwccount'] = Db::table('tps_student_answer')->where('appointment_id = '.$appointment_id.' and isok =3')->count();
        //做了的题目
        $report_ren['wccount'] = $report_ren['allcount'] - $report_ren['mwccount'];

        return view('report_answer_detail', [
            'report_question'  => $report_question,
            'report_ren'  => $report_ren,
            'time_use'  => $time_use,
        ]);
    }

    //学生tps作答页面

    public function student_tps_answer(){
        $data = Request::instance()->get();
        $appointment_id = 18;
        $search_data['id'] = $appointment_id;
        $appointment_info = Db::table('tps_appointment')->field('status,time_allow,evaluate_paper_id')->where($search_data)->find();

        $tpsinfo = New Tpsinfo();
        $paper_question_all = $tpsinfo->get_paper_students_question($appointment_info['evaluate_paper_id'],$appointment_id);


        //echo '<pre>';print_r($paper_question_all['paper_ss']);echo '<pre>';die;
        return view('student_tps_answer', [
            'paper_question'  => $paper_question_all['paper_ss'],
            'num'  => $paper_question_all['num'],
            'page'  => $data['page'],
            'xpage'  => $data['xpage'],
            'type'  => $data['type'],
        ]);
    }


}