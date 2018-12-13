<?php
namespace app\web\controller;
/*手机端端测评报告  已预约id为基本导向*/
use   \think\Request;
use   \think\Db;
use   \think\Session;
use   \app\web\controller\Cologin;
use   \app\common\controller\Tpsinfo;

class Report extends Cologin
{
    
    //学生测评报告
    public function report(){
        $data = Request::instance()->get();
        $report_id = $data['report_id'];
        $student_report  = Db::table('tps_student_report')
            ->alias('s')
            ->field('s.all_score,s.evaluate_paper_id,s.create_time,s.use_time_ms,s.dengji,s.point_id,s.point_dengji,s.point_name,s.point_bz,s.point_zq,s.point_num,s.question_all,s.tel,d.res_desc,d.zongji,d.tuijian,tep.paper_name,tep.grade')
            ->join('tps_report_dis d','d.type = s.dengji')
            ->join('tps_evaluate_paper tep','tep.id = s.evaluate_paper_id')
            ->where('s.id = '.$data['report_id'])
            ->find();
        if($student_report == null){
            echo '报告暂时没有生成';die;
        }else{
            //正确率(总分暂时没有添加)
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
            if(check_wap()){
               return view('web_report', [
                    'student_report'  => $student_report,
                    'point_array'  => $point_array,
                    'report_id'  => $report_id,
                    'tuijian_isok'  => $tuijian_isok['tuijian_kc'],
                    'tuijian'  => $tuijian,
                ]); 
           }else{
                return view('pc_report', [
                    'student_report'  => $student_report,
                    'point_array'  => $point_array,
                    'report_id'  => $report_id,
                    'tuijian_isok'  => $tuijian_isok['tuijian_kc'],
                    'tuijian'  => $tuijian,
                ]);
           }
       }
    }

    //手机版每个考点下面  题目的真确性
    public function report_answer_detail(){
        $data = Request::instance()->get();
        $student_report  = Db::table('tps_student_report')
            ->alias('a')
            ->field('p.question_all,p.id,p.point_title')
            ->join('tps_paper_point p','p.evaluate_paper_id = a.evaluate_paper_id')
            ->where('a.id = '.$data['report_id'])
            ->order('p.point_order asc')
            ->select();
        //获取考试时间
        $time_use  = Db::table('tps_student_report')
                    ->alias('tsr')
                    ->field('tsr.use_time_ms,tsr.use_time,tep.paper_name')
                    ->join('tps_evaluate_paper tep','tsr.evaluate_paper_id= tep.id')
                    ->where('tsr.id ='.$data['report_id'])
                    ->find();
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
            $report_question[$sk]['num_pd'] = Db::table('tps_student_answer')
                                            ->alias('tsa')
                                            ->field('tsa.isok,tqp.question_page,tqp.question_number,tqp.question_type')
                                            ->join('tps_paper_question tqp','tqp.id= tsa.paper_question_id')
                                            ->where('tsa.report_id ='.$data['report_id'].' and  tsa.point_id ='.$student_report[$sk]['id'])
                                            ->order('tsa.question_id asc')
                                            ->select();
            $report_question[$sk]['point_title'] = $student_report[$sk]['point_title'];
        }
        //总题目数
        $report_ren['allcount'] = Db::table('tps_student_answer')->where('report_id = '.$data['report_id'])->count();
        //正确数
        $report_ren['zqcount'] = Db::table('tps_student_answer')->where('report_id = '.$data['report_id'].' and isok =2')->count();
        //没做的题目数
        $report_ren['mwccount'] = Db::table('tps_student_answer')->where('report_id = '.$data['report_id'].' and isok =3')->count();
        //做了的题目
        $report_ren['wccount'] = $report_ren['allcount'] - $report_ren['mwccount'];
        if(check_wap()){
            return view('web_report_answer_detail', [
                'report_question'  => $report_question,
                'report_ren'  => $report_ren,
                'time_use'  => $time_use,
                'report_id'  => $data['report_id'],
            ]);
        }else{
            return view('pc_report_answer_detail', [
                'report_question'  => $report_question,
                'report_ren'  => $report_ren,
                'time_use'  => $time_use,
                'report_id'  => $data['report_id'],
            ]);
        }
        
    }

     //手机版考试所做题目的还原
    public function web_student_tps_answer(){

        $data = Request::instance()->get();
        $report_id = Db::table('tps_student_report')
                    ->alias('tsr')
                    ->field('tsr.evaluate_paper_id,tep.paper_name')
                    ->join('tps_evaluate_paper tep','tep.id= tsr.evaluate_paper_id')
                    ->where('tsr.id = '.$data['report_id'])
                    ->find();
        $tpsinfo = New Tpsinfo();
        $paper_question_all = $tpsinfo->get_paper_students_question($report_id['evaluate_paper_id'],$data['report_id']);
        return view('web_student_tps_answer', [
            'paper_name'  => $report_id['paper_name'],
            'paper_question'  => $paper_question_all['paper_ss'],
            'num'  => $paper_question_all['num'],
            'page'  => $data['page'],
            'xpage'  => $data['xpage'],
            'type'  => $data['type'],
        ]);

    }


    //pc版考试所做题目的还原
    public function pc_student_tps_answer(){
        $data = Request::instance()->get();
        $student_report  = Db::table('tps_student_report')
            ->alias('a')
            ->field('p.question_all,p.id,p.point_title')
            ->join('tps_paper_point p','p.evaluate_paper_id = a.evaluate_paper_id')
            ->where('a.id = '.$data['report_id'])
            ->order('p.point_order asc')
            ->select();
        //获取考试时间
        $time_use  = Db::table('tps_student_report')->field('use_time_ms,use_time')->where('id ='.$data['report_id'])->find();
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
            $report_question[$sk]['num_pd'] = Db::table('tps_student_answer')
                                            ->alias('tsa')
                                            ->field('tsa.isok,tqp.question_page,tqp.question_number,tqp.question_type')
                                            ->join('tps_paper_question tqp','tqp.id= tsa.paper_question_id')
                                            ->where('tsa.report_id ='.$data['report_id'].' and  tsa.point_id ='.$student_report[$sk]['id'])
                                            ->order('tsa.question_id asc')
                                            ->select();
            $report_question[$sk]['point_title'] = $student_report[$sk]['point_title'];
        }
        //总题目数
        $report_ren['allcount'] = Db::table('tps_student_answer')->where('report_id = '.$data['report_id'])->count();
        //正确数
        $report_ren['zqcount'] = Db::table('tps_student_answer')->where('report_id = '.$data['report_id'].' and isok =2')->count();
        //没做的题目数
        $report_ren['mwccount'] = Db::table('tps_student_answer')->where('report_id = '.$data['report_id'].' and isok =3')->count();
        //做了的题目
        $report_ren['wccount'] = $report_ren['allcount'] - $report_ren['mwccount'];
        return view('web_report_answer_detail', [
            'report_question'  => $report_question,
            'report_ren'  => $report_ren,
            'time_use'  => $time_use,
            'report_id'  => $data['report_id'],
        ]);
    }

    //pc版 考试测评列表
    public function pc_report_list(){

       $data = $this->get_all_report(1);
       return view('pc_report_list',[
            'data'=>$data,
            'all_page'=>$data['all_page'],
            
       ]);

    }

    //获取所有的测评报告
    public function get_all_report($page){

        $data['page'] = $page == 1 ? $page : Request::instance()->get('page');
        $limit = 2;
        $return_data['student_report']  = Db::table('tps_student_report')
        ->alias('s')
        ->field('s.evaluate_paper_id,s.create_time,s.souseid,s.id,tep.paper_name')
        ->join('tps_evaluate_paper tep','tep.id = s.evaluate_paper_id')
        ->where("s.tel =".session::get('tel'))
        ->limit($data['page'],$limit)
        ->select();

        $return_data['count']  = Db::table('tps_student_report')
        ->alias('s')
        ->field('s.evaluate_paper_id,s.create_time,s.souseid,s.id,tep.paper_name')
        ->join('tps_evaluate_paper tep','tep.id = s.evaluate_paper_id')
        ->where("s.tel =".session::get('tel'))
        ->count();
        $return_data['page'] = $data['page'];
        $return_data['all_page'] = ceil($return_data['count']/$limit);
        return $return_data;
       

    }

    //pc版 考试测评列表
    public function pc_report_common_head(){


       return view('pc_report_common_head',[
            'tel'=>session::get('tel'),
       ]);

    }

    //退出登陆
    public function log_out(){

        Session::set('student_num','');
        Session::set('tel','');
        Session::set('return_url','');
        $this->redirect('Tpsstudent/student_login');
    }



}