<?php
namespace app\web\controller;

use   \think\Request;
use   \think\Db;
use   \app\common\controller\Tpsinfo;
use   \think\Session;
class Wapanswer extends \think\Controller
{

    //考试试卷
    public function testpaper(){
        //预约id
       // $get_test_paper_id= Session::get('get_test_paper_id');
        $get_test_paper_id = 18;
        $appointment_info = Db::table('tps_appointment')
            ->alias('ta')
            ->field('ta.status,ta.evaluate_paper_id,tep.cp_sc,tep.cp_time')
            ->join('tps_evaluate_paper tep','tep.id = ta.evaluate_paper_id')
            ->where('ta.id',$get_test_paper_id)
            ->find();

        if($appointment_info['status'] == 2){
            echo '该测评这样测评一次，考生已完成该测评';die;
        }else{
            $tpsinfo = New Tpsinfo();
            $paper_question_all = $tpsinfo->get_paper_wap($appointment_info['evaluate_paper_id'],1);
            return view('testpaper', [
                'id'  => $get_test_paper_id,
                'evaluate_paper_id'  => $appointment_info['evaluate_paper_id'],
                'paper_question'  => $paper_question_all['paper_ss'],
                'num'  => $paper_question_all['num'],
                'time_allow'  => $appointment_info['cp_sc'],
                'cp_time'  => $appointment_info['cp_time']*60,
            ]);
        }

    }

    //手机端上传测评信息
    public function upload_testpaper(){

        $data = Request::instance()->get();

        $student_cp = $data['zhiall'];

      //  var_dump($data);die;
     //   echo '<pre>';print_r($student_cp);echo '<pre>';die;

        //根据用户预约id获取用户电话号码以及学科id
        $appointment_info = Db::table('tps_appointment')
            ->alias('ta')
            ->field('tep.xueke,ta.tel')
            ->join('tps_evaluate_paper tep','ta.evaluate_paper_id = tep.id')
            ->where('ta.id='.$data['appointment_id'])
            ->find();

        //总分分数
        $insert_data['appointment_id'] = $data['appointment_id'];
        $insert_data['evaluate_paper_id'] = $data['evaluate_paper_id'];
        $insert_data['tel'] = $appointment_info['tel'];
        //学科id
        $insert_data['xueke'] = $appointment_info['xueke'];


        //var_dump($insert_data);die;

        //每一小题的的得分
        $points_grade = array();
       // 得分分数
        $get_grade = 0;

        foreach($student_cp as $sk=>$sv){

            foreach($student_cp[$sk] as $ssk=>$ssv){
                //考点下面每一题的分数
                $points_grade[$ssk][$sk] = 0;
                //考点下面每一题的分数
                $point_grade = Db::table('tps_paper_point')->field('point_grade,all_grade,question_all')->where('id',$ssk)->find();
                $insert_data['point_id'] = $ssk;
                foreach($student_cp[$sk][$ssk] as $sssk=>$sssv){
                    $insert_data['question_type'] = $sssk;
                    if($sssk==1){
                        $insert_data['paper_question_id'] = $sk;
                        $insert_data['parent_id'] = 0;
                        $res = Db::table('tps_paper_question')->field('question_answer,paper_point_id')->where('id',$sk)->find();
                        $insert_data['student_answer'] = $sssv;
                        //正确的答案
                        $insert_data['ok_answer'] = $res['question_answer'];
                        if($sssv !=''){
                            if($res['question_answer']==$sssv){
                               // $points_grade[$ssk] = $point_grade['point_grade']+$points_grade[$ssk][$sk];
                                $points_grade[$ssk][$sk] = $point_grade['point_grade'];
                                $insert_data['isok'] = 1;
                                $insert_data['student_describe'] = '答案正确';
                                $insert_data['grade'] = $point_grade['point_grade'];
                            }else{
                                $insert_data['isok'] = 2;
                                $insert_data['student_describe'] = '正确答案是'.$res['question_answer'].',你选择了'.$sssv.'';
                                $insert_data['grade'] = 0;
                            }
                        }else{
                            $insert_data['isok'] = 3;
                            $insert_data['student_describe'] = '题目未完成';
                            $insert_data['grade'] = 0;
                        }
                        $insert_data['question_id'] = $this->get_point_question($ssk,$sk);
                        $this->student_answer($insert_data);
                    }else if($sssk==2){
                            $insert_data['paper_question_id'] = $sk;
                            $insert_data['parent_id'] = 0;
                            $res = Db::table('tps_paper_question')->field('question_answer,paper_point_id')->where('id',$sk)->find();
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

                            //填空题默认为没有做，
                            $insert_data['isok'] = 3;

                            //var_dump($insert_data);
                            foreach($q_answer as $qk=>$qv){
                                $kong = $qk+1;
                                if($student_cp[$sk][$ssk][$sssk][$qk]!=''){
                                    //当不为空时，默认填空题全对
                                    $insert_data['isok'] = 1;
                                    if($q_answer[$qk]==$student_cp[$sk][$ssk][$sssk][$qk]){
                                        $insert_data['grade'] = $every_point+$insert_data['grade'];
                                       // $points_grade[$sk] = $every_point+$points_grade[$sk];
                                        $insert_data['student_describe'] .= '第'.$kong.'空答案正确';
                                        $insert_data['tk_isok'] .= '1,';
                                    }else{
                                        $insert_data['student_describe'] .= '第'.$kong.'空正确答案是'.$q_answer[$qk].',你的答案是'.$student_cp[$sk][$ssk][$sssk][$qk].'。';
                                        $insert_data['tk_isok'] .= '2,';
                                        $insert_data['isok'] = 2;
                                    }
                                }else{

                                    $insert_data['student_describe'] .= '第'.$kong.'空正确答案是'.$q_answer[$qk].',你没有提交答案。';
                                }
                            }
                            $points_grade[$ssk][$sk] = $insert_data['grade'];
                            $insert_data['question_id'] = $this->get_point_question($ssk,$sk);
                            $insert_data['tk_isok'] = substr($insert_data['tk_isok'],0,strlen($insert_data['tk_isok'])-1);
                            $this->student_answer($insert_data);

                    }else if($sssk==3){
                        $insert_data['paper_question_id'] = $sk;
                        $insert_data['parent_id'] = 0;
                        //多选题题目没有完成
                      //  var_dump($student_cp[$sk][$ssk][$sssk][0]);
                        if($student_cp[$sk][$ssk][$sssk][0] == '0'){
                           // var_dump(12123);die;
                            $insert_data['isok'] = 3;
                            $insert_data['student_describe'] = '题目未完成';
                            $insert_data['grade'] = 0;
                        }else{
                            $res = Db::table('tps_paper_question')->field('question_answer')->where('id',$sk)->find();
                            $q_answer = implode(',',$student_cp[$sk][$ssk][$sssk]);
                            //正确答案
                            $insert_data['ok_answer'] = $res['question_answer'];
                            //学生所给的答案
                            $insert_data['student_answer'] = implode(',',$student_cp[$sk][$ssk][$sssk]);
                            if($res['question_answer']==$q_answer){
                                $insert_data['grade'] = $point_grade['point_grade'];
                               // $points_grade[$sk] = $point_grade['point_grade']+$points_grade[$sk];
                                $points_grade[$ssk][$sk] = $point_grade['point_grade'];
                                $insert_data['student_describe'] = '答案正确。';
                                $insert_data['isok'] = 1;
                            }else{
                                $insert_data['student_describe'] = '正确答案是'.$res['question_answer'].',而你选择了'.$q_answer.'！';
                                $insert_data['isok'] = 2;
                            }
                        }
                        $insert_data['question_id'] = $this->get_point_question($ssk,$sk);
                        $this->student_answer($insert_data);

                    }else if($sssk==4 || $sssk==5){

                        $insert_data['paper_question_id'] = $sk;
                        $res = Db::table('tps_paper_question')->field('question_answer,paper_point_id,question_number,parent_id')->where('id',$sk)->find();

                        $insert_data['ok_answer'] =$res['question_answer'];
                        $insert_data['student_answer'] = $sssv;
                        $insert_data['parent_id'] = $res['parent_id'];
                        if($sssv !='') {
                            if ($res['question_answer'] == $sssv) {
                                $points_grade[$ssk][$sk] = $point_grade['point_grade'];
                                $insert_data['isok'] = 1;
                                $insert_data['student_describe'] = '答案正确';
                                $insert_data['grade'] = $point_grade['point_grade'];
                            } else {
                                $insert_data['isok'] = 2;
                                $insert_data['student_describe'] = '正确答案是' . $res['question_answer'] . ',你选择了' . $sssv . '';
                                $insert_data['grade'] = 0;
                                $this->wxyd($insert_data);
                            }
                        }else{
                            $insert_data['isok'] = 3;
                            $insert_data['student_describe'] = '题目未完成';
                            $insert_data['grade'] = 0;
                            $this->wxyd($insert_data);
                        }
                        $insert_data['question_id'] = $this->get_point_question($ssk,$sk);
                        $this->student_answer($insert_data);
                    }
                }
            }
        }
        //获取每个考点下面的分数总和
        $point_array = array();

        foreach($points_grade as $ik=>$iv){
            $point_array[$ik] = 0;
            foreach($points_grade[$ik] as $iik=>$iiv){
                $point_array[$ik] = $point_array[$ik] +$iiv;
                $get_grade = $get_grade +$iiv;
            }
        }

        //根据考点分数 总得分  试卷 ==》》  考点所占比重  正确率  等级 名称
        $student_report = $this->point_detail($point_array,$insert_data['evaluate_paper_id'],$get_grade);
        $student_report['appointment_id'] = $insert_data['appointment_id'];
        //用时
        $student_report['use_time'] = $data['use_time'];
        $student_report['use_time_ms'] = $this->usetime($data['use_time']);
        //$student_report['create_time'] = time();
        $student_report['create_time'] = date("Y-m-d H:i:s", time());
        Db::table('tps_student_report')->insert($student_report);
        return 1;
    }

    //完型填空和阅读理解做错后的选择
    public function wxyd($insert_data){

        //判断完型填空和阅读主题干是否存在
        $cz = Db::table('tps_student_answer')->where('tel ='.$insert_data['tel'].' and evaluate_paper_id ='.$insert_data['evaluate_paper_id'].' and appointment_id ='.$insert_data['appointment_id'].' and paper_question_id ='.$insert_data['parent_id'])->count();

        if($cz==0){
            $insert_data['paper_question_id'] = $insert_data['parent_id'];
            $insert_data['parent_id'] = 0;
            $insert_data['tg_type'] = 1;
            Db::table('tps_student_answer')->insert($insert_data);

        }




    }

    //将学生做的每一题都添加到tps_student_answer表中
    public function student_answer($data){


        return Db::table('tps_student_answer')->insert($data);
    }

    //获取考点下面的题号(发布试卷后添加题号可能最佳)
    public function get_point_question($pointid,$questionid){

        $paper_questions = Db::table('tps_paper_question')->field('question_type,id')->where('paper_point_id = '.$pointid.' and parent_id = 0')->order('question_number asc')->select();
        $n = 0;
        $arr = array();
        foreach($paper_questions as $pk=>$pv){

            if($paper_questions[$pk]['question_type'] == 1 || $paper_questions[$pk]['question_type'] == 2 || $paper_questions[$pk]['question_type'] ==3 ){
                $n++;
                $arr[$paper_questions[$pk]['id']] = $n;
            }else if($paper_questions[$pk]['question_type'] == 4 || $paper_questions[$pk]['question_type'] == 5){
                $paper_questionss = Db::table('tps_paper_question')->field('question_type,id')->where('parent_id = '.$paper_questions[$pk]['id'])->order('question_number asc')->select();
                foreach($paper_questionss as $sk=>$sv){
                    $n++;
                    $arr[$paper_questionss[$sk]['id']] = $n;
                }
            }
        }
       // var_dump($arr);die;
        return $arr[$questionid];
    }

    //花费时间呈现    $usetime==>完成试卷用的秒数
    public function usetime($usetime){
        $theTime0 = 0;// 秒
        $theTime1 = 0;// 分
        if($usetime > 60) {
            $theTime1 = floor($usetime/60);
            $theTime0 = $usetime%60;
        }else{
            $theTime0 = $usetime;
        }


        $result = '';
        if( $theTime1==0){
            $result = $theTime0.'秒';
        }else if($theTime1!=0){
            $result = $theTime1.'分'.$theTime0.'秒';
        }

        return $result;
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
        $allscore = Db::table('tps_paper_point')->where('evaluate_paper_id ='.$evaluate_paper_id)->sum('all_grade');
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
}