<?php
namespace app\common\controller;
use  think\Db;
use think\Controller;
header("Content-Type:text/html;charset=utf8");

class Tpsinfo {

    //pc端题目显示
    public function get_paper($evaluate_paper_id){

        $paper_title = Db::table('tps_evaluate_paper')->where('id',$evaluate_paper_id)->find();
        $paper_point = Db::table('tps_paper_point')->where('evaluate_paper_id',$paper_title['id'])->order('point_order desc, id asc')->select();
        $paper_question = '';
        foreach($paper_point as $pk=>$pv){
            $paper_question[$pk] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',0)->order('question_number asc')->select();
            foreach($paper_question[$pk] as $ssk=>$ssv) {
                $paper_question[$pk][$ssk]['contents'] = explode("{#}", $paper_question[$pk][$ssk]['question_content']);
                $paper_question[$pk][$ssk]['children'] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',$paper_question[$pk][$ssk]['id'])->order('question_number asc')->select();
                foreach($paper_question[$pk][$ssk]['children'] as $sssk=>$sssv){
                    $paper_question[$pk][$ssk]['children'][$sssk]['question_answer_shuzi'] = $this->question_answer($paper_question[$pk][$ssk]['children'][$sssk]['question_answer']);
                    $paper_question[$pk][$ssk]['children'][$sssk]['contents'] =  explode("{#}",$paper_question[$pk][$ssk]['children'][$sssk]['question_content']);
                }
            }
        }
       // echo '<pre>';print_r($paper_question);echo '<pre>';die;
        $page = 1;
        $paper_page = array();
        $num = 1;
        foreach($paper_question as $pk=>$pv){
            $arrcount = count($paper_question[$pk]);
            foreach($paper_question[$pk] as $ppk=>$ppv){
                $paper_question[$pk][$ppk]['test_paper_num'] = $num++;
                if($paper_question[$pk][$ppk]['question_type'] == 4 || $paper_question[$pk][$ppk]['question_type'] == 5){

                    $paper_page[$page][] = $paper_question[$pk][$ppk];
                    $page++;
                }else{

                    $pagej = $ppk + 1;
                    if($pagej != $arrcount && $paper_question[$pk][$ppk]['question_type'] != $paper_question[$pk][$pagej]['question_type'] ){
                        $paper_page[$page][] = $paper_question[$pk][$ppk];
                        $page++;
                    }else if($pagej != $arrcount && $paper_question[$pk][$ppk]['question_type'] == $paper_question[$pk][$pagej]['question_type']){
                        //每一页的条数不大于5
                        $paper_page[$page][] = $paper_question[$pk][$ppk];
                        $pagecount = count($paper_page[$page]);
                        if($pagecount>4){
                            $page++;
                        }
                    }else if($pagej == $arrcount){
                        $paper_page[$page][] = $paper_question[$pk][$ppk];
                        $page++;
                    }
                }
            }
        }
        //echo '<pre>';print_r($paper_page);echo '<pre>';
        $count = Db::table('tps_paper_question')->where('evaluate_paper_id',$evaluate_paper_id)->where('parent_id',0)->count();
        $data['paper_question'] = $paper_page;
        $data['pageall'] = count($paper_page);
        $data['count'] = $count;
        return $data;

    }
    //题目选择答案
    public function question_answer($ss){

        switch ($ss) {
            case 'A':
                $re = 1;
                break;
            case 'B':
                $re = 2;
                break;
            case 'C':
                $re = 3;
                break;
            case 'D':
                $re = 4;
                break;
            case 'E':
                $re = 5;
                break;
            case 'F':
                $re = 6;
                break;
            default:
                $re = 0;
        }
        return $re;
    }
    //wap端题目显示
    public function get_paper_wap($evaluate_paper_id){

        $paper_title = Db::table('tps_evaluate_paper')->where('id',$evaluate_paper_id)->find();
        $paper_point = Db::table('tps_paper_point')->where('evaluate_paper_id',$paper_title['id'])->order('point_order desc, id asc')->select();
        $paper_question = '';
        foreach($paper_point as $pk=>$pv){
            $paper_question[$pk] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',0)->order('question_number asc')->select();
            foreach($paper_question[$pk] as $ssk=>$ssv) {
                $paper_question[$pk][$ssk]['contents'] = explode("{#}", $paper_question[$pk][$ssk]['question_content']);
                $paper_question[$pk][$ssk]['children'] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',$paper_question[$pk][$ssk]['id'])->order('question_number asc')->select();
                $paper_question[$pk][$ssk]['count'] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',$paper_question[$pk][$ssk]['id'])->count();

                foreach($paper_question[$pk][$ssk]['children'] as $sssk=>$sssv){
                    $paper_question[$pk][$ssk]['children'][$sssk]['question_answer_shuzi'] = $this->question_answer($paper_question[$pk][$ssk]['children'][$sssk]['question_answer']);
                    $paper_question[$pk][$ssk]['children'][$sssk]['contents'] =  explode("{#}",$paper_question[$pk][$ssk]['children'][$sssk]['question_content']);
                }
            }
        }
        $paper_ss = array();
        $num = 1;

        foreach($paper_question as $pk=>$pv){
            //设置题号以及页码数
            foreach($paper_question[$pk] as $ppk=>$ppv){
                $paper_ss[$num] = $paper_question[$pk][$ppk];
                $num++;
            }
        }
        $data['paper_ss'] = $paper_ss;
        $data['num'] = $num-1;
        //echo '<pre>';print_r($paper_ss);echo '<pre>';die;
        //$count = Db::table('tps_paper_question')->where('evaluate_paper_id',$evaluate_paper_id)->where('parent_id',0)->count();
        //$data['count'] = $count;
        return $data;

    }

    //wap端学生单科题目展示   $evaluate_paper_id==》试卷id  $appointment_id==>预约id
    public function get_paper_students_question($evaluate_paper_id,$appointment_id){

        $paper_title = Db::table('tps_evaluate_paper')->where('id',$evaluate_paper_id)->find();
        $paper_point = Db::table('tps_paper_point')->where('evaluate_paper_id',$paper_title['id'])->order('point_order desc, id asc')->select();
        $paper_question = '';
        foreach($paper_point as $pk=>$pv){
            $paper_question[$pk] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',0)->order('question_number asc')->select();
            foreach($paper_question[$pk] as $ssk=>$ssv) {
                $paper_question[$pk][$ssk]['contents'] = explode("{#}", $paper_question[$pk][$ssk]['question_content']);
                $paper_question[$pk][$ssk]['children'] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',$paper_question[$pk][$ssk]['id'])->order('question_number asc')->select();
                $paper_question[$pk][$ssk]['count'] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',$paper_question[$pk][$ssk]['id'])->count();

                $paper_question[$pk][$ssk]['question_answer_shuzi'] = $this->question_answer($paper_question[$pk][$ssk]['question_answer']);

                foreach($paper_question[$pk][$ssk]['children'] as $sssk=>$sssv){
                    $paper_question[$pk][$ssk]['children'][$sssk]['question_answer_shuzi'] = $this->question_answer($paper_question[$pk][$ssk]['children'][$sssk]['question_answer']);
                    $paper_question[$pk][$ssk]['children'][$sssk]['contents'] =  explode("{#}",$paper_question[$pk][$ssk]['children'][$sssk]['question_content']);
                }
            }
        }
        $paper_ss = array();
        $num = 1;
        foreach($paper_question as $pk=>$pv){
            //学生答题情况

            foreach($paper_question[$pk] as $ppk=>$ppv){
                if($ppv['question_type'] == 4 || $ppv['question_type'] == 5){

                    foreach($paper_question[$pk][$ppk]['children'] as $pppk=>$pppv){
                        $tps_student_answer = Db::table('tps_student_answer')->field('isok,student_answer')->where('paper_question_id ='.$paper_question[$pk][$ppk]['children'][$pppk]['id'].' and appointment_id ='.$appointment_id)->find();
                        $paper_question[$pk][$ppk]['children'][$pppk]['student_answer'] = $tps_student_answer['student_answer'];
                        $paper_question[$pk][$ppk]['children'][$pppk]['answer_isok'] = $tps_student_answer['isok'];
                        $paper_question[$pk][$ppk]['children'][$pppk]['student_question_answer_shuzi'] = $this->question_answer($tps_student_answer['student_answer']);
                    }

                }else{
                    $tps_student_answer = Db::table('tps_student_answer')->field('isok,student_answer,student_describe')->where('paper_question_id ='.$paper_question[$pk][$ppk]['id'].' and appointment_id ='.$appointment_id)->find();
                    $paper_question[$pk][$ppk]['student_answer'] = $tps_student_answer['student_answer'];
                    $paper_question[$pk][$ppk]['answer_isok'] = $tps_student_answer['isok'];
                    $paper_question[$pk][$ppk]['student_describe'] = $tps_student_answer['student_describe'];
                    if($ppv['question_type'] == 1){
                        $paper_question[$pk][$ppk]['student_question_answer_shuzi'] = $this->question_answer($tps_student_answer['student_answer']);
                    }elseif($ppv['question_type'] == 3){
                        $question_answer = array();
                        $ss = explode(',',$paper_question[$pk][$ppk]['question_answer']);
                        foreach($ss as $sk=>$sv){
                            $question_answer[] = $this->question_answer($sv);
                        }
                        //将真确答案列出
                        foreach($paper_question[$pk][$ppk]['contents'] as $lk=>$lv){

                            $paper_question[$pk][$ppk]['pp'][$lk]['contents'] = $lv;
                            $paper_question[$pk][$ppk]['pp'][$lk]['is_answer'] = 0;
                            $new_val = $lk + 1;
                            foreach($question_answer as $sk=>$sv){
                                if($new_val == $sv){
                                    $paper_question[$pk][$ppk]['pp'][$lk]['is_answer'] = 1;
                                }
                            }
                        }
                       // var_dump($paper_question);die;
                      //  $paper_question[$pk][$ppk]['question_answer_shuzi'] = $question_answer;

                    }elseif($ppv['question_type'] == 2){


                    }


                }
            }
        }

        foreach($paper_question as $pk=>$pv){
            //设置题号以及页码数
            foreach($paper_question[$pk] as $ppk=>$ppv){
                $paper_ss[$num] = $paper_question[$pk][$ppk];
                $num++;
            }
        }
        $data['paper_ss'] = $paper_ss;
        $data['num'] = $num-1;
        return $data;

    }


    //错误题目
    public function cwtm_only(){

        $data = Request::instance()->get();

        //$tel = $this->tel;
        //$data = Request::instance()->get();
        // $xueke = $this->xueke;
        //下一页错题题号
        $nextpage = $data['curPage']*10 + $data['page'] + 1;
        //上一页错题题号
        $prepage = $data['curPage']*10 + $data['page'] - 1;
        //错题题号
        $curPage = $data['curPage']*10 + $data['page'];




        return view('cwtm_only', [
            //'question_id'  =>$data['question_id'],
            'nextpage'  =>$nextpage,
            'prepage'  =>$prepage,
            'curpage'  =>$curpage,
        ]);
    }

    //单个错误题目
    public function return_cwtm_only(){

        $data = Request::instance()->get();

        $tel = $this->tel;
        //$data = Request::instance()->get();
        // $xueke = $this->xueke;
        //下一页错题题号
        //$nextpage = $data['curPage']*10 + $data['page'] + 1;
        $allNum = Db::table('tps_student_answer')
            ->alias('tsa')
            ->field('tep.*,tsa.student_answer,tsa.student_describe')
            ->join('tps_paper_question tep','tsa.paper_question_id = tep.id')
            ->where('tsa.tel='.$tel.' and tsa.isok !=1 and tsa.paper_question_id ='.$data['question_id'])
            ->find();


        if($allNum['question_type'] == 4 || $allNum['question_type'] == 5){

            $allNum['xt'] = Db::table('tps_student_answer')


                ->where('parent_id ='.$allNum['paper_question_id'].' and tel ='.$tel.' and isok !=1')
                ->select();

            foreach($allNum['xt'] as $ak=>$av){
                $allNum['xt'][$ak]['contents'] =  explode("{#}",$allNum['xt'][$ak]['question_content']);
            }
        }elseif($allNum['question_type'] == 1 || $allNum['question_type'] == 3){
            $allNum['contents'] =  explode("{#}",$allNum['question_content']);
        }

        return view('return_cwtm_only', [
            'allNum'  =>$allNum,
            'question_type'  =>$allNum['question_type'],
        ]);


    }

}