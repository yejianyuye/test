<?php
namespace app\web\controller;

use   \think\Request;
use   \think\Db;
use   \app\common\controller\Tpsinfo;
use   \think\Session;
class Pcanswer extends \think\Controller
{


    //考试试卷
    public function testpaper(){
        //预约id
      // $get_test_paper_id= Session::get('get_test_paper_id');
        //$search_data['id'] = $get_test_paper_id;
        $get_test_paper_id = 18;
        $search_data['id'] = $get_test_paper_id;
        $appointment_info = Db::table('tps_appointment')->field('status,time_allow,evaluate_paper_id')->where($search_data)->find();
       //$tel= Session::get('tel');


      //  if(!array_key_exists('evaluate_paper_id',$data)  || $data['evaluate_paper_id']== ''){
     //       echo '没有选择考卷';die;
       // }

       // if (!$get_test_paper_id){
        //    $this->redirect('Tps/testpaper_login', array('evaluate_paper_id'=>$data['evaluate_paper_id']));
       // }else{
           // $search_data['evaluate_paper_id'] = $data['evaluate_paper_id'];

           // $search_data['tel'] = $tel;
            //var_dump($search_data);die;

            //var_dump($appointment_info);die;
            if($appointment_info['status'] == 2){
                echo '该测评这样测评一次，考生已完成该测评';die;
            }else{
                $tpsinfo = New Tpsinfo();
                $paper_question_all = $tpsinfo->get_paper($appointment_info['evaluate_paper_id'],1);
                return view('testpaper', [
                    'id'  => $get_test_paper_id,
                    'paper_question'  => $paper_question_all['paper_question'],
                    'count'  => $paper_question_all['count'],
                    'pageall'  => $paper_question_all['pageall'],
                    'time_allow'  => $appointment_info['time_allow'],
                    'evaluate_paper_id'  => $appointment_info['evaluate_paper_id'],
                ]);
            }
       // }
    }

    //线上提交测评信息
    public function upload_testpaper(){

        $data = Request::instance()->get();
        //学生测评结果
        $student_cp = $data['wode'];
       // var_dump($student_cp);die;
        //得分分数
        $get_grade = 0;
        //总分分数
        //$all_grade = 0;
        $insert_data['appointment_id'] = $data['appointment_id'];
        $insert_data['evaluate_paper_id'] = $data['evaluate_paper_id'];

      //  $pointid_str = '';
       // $pointdj_str = '';
        $points_grade = array();
        //每一题答案的分析
        foreach($student_cp as $sk=>$sv){
            $points_grade[$sk] = 0;
            $point_grade = Db::table('tps_paper_point')->field('point_grade,all_grade,question_all')->where('id',$sk)->find();
            // var_dump($sk);die;
            //考点id
            $insert_data['point_id'] = $sk;
            foreach($student_cp[$sk] as $ssk=>$ssv){
                //$insert_data['paper_point_id'] = $sk;
                $insert_data['question_type'] = $ssk;
                if($ssk==1){
                    foreach($student_cp[$sk][$ssk] as $sssk=>$sssv){
                        $res = Db::table('tps_paper_question')->field('question_answer,paper_point_id')->where('id',$sssk)->find();
                        $insert_data['student_answer'] = $sssv;
                        //正确的答案
                        $insert_data['ok_answer'] = $res['question_answer'];
                       // $point_grade = Db::table('tps_paper_point')->field('point_grade')->where('id',$res['paper_point_id'])->find();
                        if($res['question_answer']==$sssv){
                            $points_grade[$sk] = $point_grade['point_grade']+$points_grade[$sk];
                            $insert_data['isok'] = 1;
                            $insert_data['student_describe'] = '答案正确';
                            $insert_data['grade'] = $point_grade['point_grade'];
                        }else{
                            $insert_data['isok'] = 2;
                            $insert_data['student_describe'] = '正确答案是'.$res['question_answer'].',你选择了'.$sssv.'';
                            $insert_data['grade'] = 0;
                        }
                        $insert_data['question_id'] = $this->get_point_question($sk,$sssk);
                        $this->student_answer($insert_data);
                    }
                }else if($ssk==2){
                    foreach($student_cp[$sk][$ssk] as $sssk=>$sssv){
                        $res = Db::table('tps_paper_question')->field('question_answer,paper_point_id')->where('id',$sssk)->find();
                       // $point_grade = Db::table('tps_paper_point')->field('point_grade')->where('id',$res['paper_point_id'])->find();
                        $nun = count($student_cp[$sk][$ssk][$sssk]);
                        $every_point = sprintf("%.1f", $point_grade['point_grade']/$nun);
                        $q_answer = explode('{#}',$res['question_answer']);
                        //正确答案
                        $insert_data['ok_answer'] = $res['question_answer'];
                        //学生所给的答案
                        $insert_data['student_answer']  = implode('{#}',$student_cp[$sk][$ssk][$sssk]);
                        $insert_data['student_describe'] = '';
                        $insert_data['grade'] = 0;
                        $insert_data['tk_isok'] = '';
                        foreach($q_answer as $qk=>$qv){
                            $kong = $qk+1;
                            $insert_data['isok'] = 1;
                            if($q_answer[$qk]==$student_cp[$sk][$ssk][$sssk][$qk]){
                                $insert_data['grade'] = $every_point+$insert_data['grade'];
                                $points_grade[$sk] = $every_point+$points_grade[$sk];
                                $insert_data['student_describe'] .= '第'.$kong.'空答案正确';
                                $insert_data['tk_isok'] .= '1,';
                            }else{
                                $insert_data['student_describe'] .= '第'.$kong.'空正确答案是'.$q_answer[$qk].',你的答案是'.$student_cp[$sk][$ssk][$sssk][$qk].'。';
                                $insert_data['tk_isok'] .= '2,';
                                $insert_data['isok'] = 2;
                            }
                        }
                        $insert_data['question_id'] = $this->get_point_question($sk,$sssk);
                        $insert_data['isok'] = substr($insert_data['isok'],0,strlen($insert_data['isok'])-1);
                        $this->student_answer($insert_data);
                    }

                }else if($ssk==3){
                    foreach($student_cp[$sk][$ssk] as $sssk=>$sssv){
                        $res = Db::table('tps_paper_question')->field('question_answer')->where('id',$sssk)->find();
                        //$point_grade = Db::table('tps_paper_point')->field('point_grade')->where('id',$res['paper_point_id'])->find();
                        $q_answer = implode(',',$student_cp[$sk][$ssk][$sssk]);
                        //正确答案
                        $insert_data['ok_answer'] = $res['question_answer'];
                        //学生所给的答案
                        $insert_data['student_answer'] = implode(',',$student_cp[$sk][$ssk][$sssk]);
                        if($res['question_answer']==$q_answer){
                            $insert_data['grade'] = $point_grade['point_grade'];
                            $points_grade[$sk] = $point_grade['point_grade']+$points_grade[$sk];
                            $insert_data['student_describe'] = '答案正确。';
                            $insert_data['isok'] = '1';
                        }else{
                            $insert_data['student_describe'] = '正确答案是'.$res['question_answer'].',而你选择了'.$q_answer.'！';
                            $insert_data['isok'] .= '2';
                        }
                        $insert_data['question_id'] = $this->get_point_question($sk,$sssk);
                        $this->student_answer($insert_data);
                    }
                }else if($ssk==4 || $ssk==5){
                    foreach($student_cp[$sk][$ssk] as $sssk=>$sssv){
                        $res = Db::table('tps_paper_question')->field('question_answer,paper_point_id,question_number')->where('id',$sssk)->find();
                        $insert_data['ok_answer'] =$res['question_answer'];
                        $insert_data['student_answer'] = $sssv;
                       // $point_grade = Db::table('tps_paper_point')->field('point_grade')->where('id',$res['paper_point_id'])->find();
                        if($res['question_answer']==$sssv){
                            $points_grade[$sk] = $point_grade['point_grade']+$points_grade[$sk];
                            $insert_data['isok'] = 1;
                            $insert_data['student_describe'] = '答案正确';
                            $insert_data['grade'] = $point_grade['point_grade'];
                        }else{
                            $insert_data['isok'] = 2;
                            $insert_data['student_describe'] = '正确答案是'.$res['question_answer'].',你选择了'.$sssv.'';
                            $insert_data['grade'] = 0;
                        }
                        $insert_data['question_id'] = $this->get_point_question($sk,$sssk);
                        $this->student_answer($insert_data);
                    }

                }
            }
            $get_grade = $points_grade[$sk] + $get_grade;
        }

        $student_report = $this->point_detail($points_grade,$insert_data['evaluate_paper_id'],$get_grade);
        $student_report['appointment_id'] = $data['appointment_id'];

        Db::table('tps_student_report')->insert($student_report);



        //学生提交试卷完成  此次预约测评结束
        //Db::table('tps_student_answer')->where('id',$insert_data['appointment_id'])->update(['status' => '2']);


       // echo '<pre>';print_r($data);echo '<pre>';die;
    }

    //将学生做的每一题都添加到tps_student_answer表中
    public function student_answer($data){


        return Db::table('tps_student_answer')->insert($data);
    }

    //获取考点下面的题号
    public function get_point_question($pointid,$questionid){

        $paper_questions = Db::table('tps_paper_question')->field('question_type,id')->where('paper_point_id = '.$pointid.' and parent_id = 0')->order('question_number asc')->select();
        $n = 0;
        $arr = array();
        foreach($paper_questions as $pk=>$pv){

            if($paper_questions[$pk]['question_type'] == 1 || $paper_questions[$pk]['question_type'] == 2 || $paper_questions[$pk]['question_type'] ==3 ){
                $n++;
                $arr[$paper_questions[$pk]['id']] = $n;
            }else if($paper_questions[$pk]['question_type'] == 4 || $paper_questions[$pk]['question_type'] == 5){

                $paper_questionss = Db::table('tps_paper_question')->field('question_type,id')->where('parent_id = '.$paper_questions[$pk]['id'])->select();
               //var_dump($paper_questionss);die;
                foreach($paper_questionss as $sk=>$sv){
                    $n++;
                    $arr[$paper_questionss[$sk]['id']] = $n;
                }

            }
        }
        //var_dump($arr);die;
        return $arr[$questionid];
    }

    //测评等级判定
    public function tps_dengji($data){

        $res = 1;
        if($data>=0.9){
            $res = 1;
        }elseif($data>=0.8 && $data<0.9){
            $res = 2;
        }elseif($data>=0.6 && $data<0.8){
            $res = 3;
        }elseif($data<0.6){
            $res = 4;
        }
        return $res;
    }

    //测评等级判定
    public function tps_point_dengji($data){

        $res = 1;
        if($data==1){
            $res = 1;
        }elseif($data>=0.9 && $data<0.1){
            $res = 2;
        }elseif($data>=0.8 && $data<0.9){
            $res = 3;
        }elseif($data>=0.6 && $data<0.8){
            $res = 4;
        }elseif($data<0.6){
            $res = 5;
        }
        return $res;
    }

    //试卷考点所占比重  正确率  等级 名称
    public function point_detail($array,$evaluate_paper_id,$getdata){

        $paper_point = Db::table('tps_paper_point')->where('evaluate_paper_id ='.$evaluate_paper_id)->order('point_order asc')->select();
        //试卷总分
        $allscore = Db::table('tps_paper_point')->sum('all_grade');
        $return_data = array();
        $return_data['point_id'] = '';
        $return_data['point_name'] = '';
        $return_data['point_bz'] = '';
        $return_data['point_dengji'] = '';
        $return_data['point_zq'] = '';
        $return_data['question_all'] = '';
        $return_data['point_num'] = '';
        foreach($paper_point as $pk=>$pv){
            $return_data['point_id'] .= $paper_point[$pk]['id'].',';
            $return_data['point_name'] .= $paper_point[$pk]['point_title'].',';
            $return_data['question_all'] .= $paper_point[$pk]['question_all'].',';
            $return_data['point_bz'] .= number_format($paper_point[$pk]['all_grade']/$allscore,4).',';
            if(array_key_exists($paper_point[$pk]['id'],$array)){
                $every_pointdj = number_format($array[$paper_point[$pk]['id']]/$paper_point[$pk]['all_grade'],4);
                $return_data['point_num'] .= number_format($array[$paper_point[$pk]['id']]/$paper_point[$pk]['point_grade'],0).',';
                $return_data['point_dengji'] .= $this->tps_point_dengji($every_pointdj).',';
                $return_data['point_zq'] .= $every_pointdj.',';
            }else{
                $return_data['point_zq'] .= '0,';
                $return_data['point_dengji'] .= '5,';
                $return_data['point_num'] .= '0,';
            }
        }
        $return_data['point_id'] = substr($return_data['point_id'],0,strlen($return_data['point_id'])-1);
        $return_data['point_name'] = substr($return_data['point_name'],0,strlen($return_data['point_name'])-1);
        $return_data['point_bz'] = substr($return_data['point_bz'],0,strlen($return_data['point_bz'])-1);
        $return_data['point_dengji'] = substr($return_data['point_dengji'],0,strlen($return_data['point_dengji'])-1);
        $return_data['point_zq'] = substr($return_data['point_zq'],0,strlen($return_data['point_zq'])-1);
        $return_data['point_num'] = substr($return_data['point_num'],0,strlen($return_data['point_num'])-1);
        $return_data['question_all'] = substr($return_data['question_all'],0,strlen($return_data['question_all'])-1);
        $return_data['all_score'] = $getdata;
        $all_pointdj = sprintf("%.1f", $return_data['all_score']/$allscore);
        $return_data['dengji'] =  $this->tps_dengji($all_pointdj);

        return $return_data;


    }

    //测评报告
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

        return view('report', [
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