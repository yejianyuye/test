<?php
namespace app\web\controller;
/*手机端端测评报告  已预约id为基本导向*/
use   \think\Request;
use   \think\Db;
use   \think\Session;
use   \app\web\controller\Cologin;

class Tpsstudentinfo extends Cologin
{

    //public  $tel = '13419541891';
   // public  $xueke = 24;

    //推出登陆
    public function del_session(){

        $sm_del = Session::delete('tel');
        $se_del = Session::delete('student_num');
        $this->redirect('Tpsstudent/student_login');

    }

    //tps查询入口
    public function student_index(){

        return view('student_index',[
            'student_num'=>session::get('student_num'),
            'tel'=>session::get('tel')
        ]);
    }

    //tps诊断报告
    public function report_list(){

        //电话号码==》诊断id==>诊断的试卷
        $student_report=Db::table('tps_student_report')
                        ->alias('s')
                        ->field('s.evaluate_paper_id,s.souseid,s.id,tep.paper_name')
                        ->join('tps_evaluate_paper tep','tep.id = s.evaluate_paper_id')
                        ->where("s.tel =".session::get('tel'))
                        ->select();

        $count = Db::table('tps_student_report')->where("tel =".session::get('tel'))->count();

        return view('report_list', [
            'student_report'  => $student_report,
            'count'  => $count,
        ]);
    }

    //tps诊断曲线
    public function testscore(){


        return view();
    }


    //tps 记错本
    public function errorsubject(){

        $tel = session::get('tel');
        //以电话号码为主线   进行学号考试科目错题汇总
        $appointment_info = Db::table('tps_student_report')
            ->alias('tsr')
            ->field('tpc.name,tep.xueke,tsr.evaluate_paper_id')
            ->join('tps_evaluate_paper tep','tep.id = tsr.evaluate_paper_id')
            ->join('tps_paper_class tpc','tpc.id = tep.xueke')
            ->where('tsr.tel',$tel)
            ->group('tep.xueke')
            ->select();

        return view('errorsubject', [
            'appointment_info'  => $appointment_info,
        ]);
    }

    //参加测评的学科的错题
    public function cwtm(){
        //$tel = $this->tel;
        $data = Request::instance()->get();
        //$data['xueke'] = $this->xueke;
        $data['curPage'] = 1;
        $data['pageSize'] = 10;


        $allNum = Db::table('tps_student_answer')
                ->where('tel='.$tel.' and isok !=1 and question_type !=4 and question_type !=5 and xueke='.$data['xueke'])
                ->whereor('tel='.$tel.' and tg_type =1  and xueke='.$data['xueke'])
                ->count();
        $data['allNum'] = ceil($allNum/$data['pageSize']);
        $data['tel'] = $tel;

        return view('cwtm', [
            'data'  => $data,
        ]);
    }

    //错误题目
    public function return_cwtm(){

        $data = Request::instance()->get();
        $appointment_info = Db::table('tps_student_answer')
            ->alias('tsa')
            ->field('tep.*')
            ->join('tps_paper_question tep','tsa.paper_question_id = tep.id')
            ->where('tsa.tel='.$data['tel'].' and tsa.isok !=1 and tsa.question_type !=4 and tsa.question_type !=5 and tsa.xueke='.$data['xueke'])
            ->whereor('tsa.tel='.$data['tel'].' and tsa.tg_type =1  and tsa.xueke='.$data['xueke'])

            ->page($data['curPage'],$data['pageSize'])
            ->order('id desc')
            ->select();
        foreach($appointment_info as $ak=>$av){
            if($appointment_info[$ak]['question_type']== 1 || $appointment_info[$ak]['question_type']== 3){
                $appointment_info[$ak]['question_content'] = explode("{#}", $appointment_info[$ak]['question_content']);
            }
            //同一人下面  一道完型填空或者一道阅读理解下面错误的题目
            if($appointment_info[$ak]['question_type']== 4 || $appointment_info[$ak]['question_type']== 5){

                $appointment_info[$ak]['count'] = Db::table('tps_student_answer')->where('tel='.$data['tel'].' and  isok != 1 and parent_id ='.$appointment_info[$ak]['id'])->group('paper_question_id')->count();

            }
        }
        return view('return_cwtm', [
            'appointment_info'  => $appointment_info,
            'curPage'  => $data['curPage'],
        ]);
    }

    //错误题目
    public function cwtm_only(){

        $data = Request::instance()->get();
        $tel = $this->tel;
         $xueke = $this->xueke;

        //当前错题题号
        $curpage = ($data['curPage']-1)*10 + $data['page'];
        //总页数
        $num = Db::table('tps_student_answer')
            ->alias('tsa')
            ->field('tep.*,tsa.student_answer,tsa.student_describe,tsa.paper_question_id')
            ->join('tps_paper_question tep','tsa.paper_question_id = tep.id')
            ->where('tsa.tel='.$tel.' and tsa.isok !=1 and tsa.question_type !=4 and tsa.question_type !=5 and tsa.xueke='.$xueke)
            ->whereor('tsa.tel='.$tel.' and tsa.tg_type =1  and tsa.xueke='.$xueke)
            ->count();

        return view('cwtm_only', [
            'curpage'  =>$curpage,
            'count'  =>$num,
        ]);
    }

    //单个错误题目
    public function return_cwtm_only(){

        $data = Request::instance()->get();
        $tel = $this->tel;
        $xueke = $this->xueke;

        //下一页错题题号
        $num = Db::table('tps_student_answer')
            ->alias('tsa')
            ->field('tep.*,tsa.student_answer,tsa.student_describe,tsa.paper_question_id')
            ->join('tps_paper_question tep','tsa.paper_question_id = tep.id')
            ->where('tsa.tel='.$tel.' and tsa.isok !=1 and tsa.question_type !=4 and tsa.question_type !=5 and tsa.xueke='.$xueke)
            ->whereor('tsa.tel='.$tel.' and tsa.tg_type =1  and tsa.xueke='.$xueke)
            ->page($data['curpage'],1)
            ->order('id desc')
            ->select();
        $allNum = $num[0];
        if($allNum['question_type'] == 4 || $allNum['question_type'] == 5){

            $allNum['xt'] = Db::table('tps_student_answer')
                            ->alias('tsa')
                            ->field('tep.*,tsa.student_answer,tsa.student_describe,tsa.paper_question_id')
                            ->join('tps_paper_question tep','tsa.paper_question_id = tep.id')
                            ->where('tsa.parent_id ='.$allNum['paper_question_id'].' and tsa.tel ='.$tel.' and tsa.isok !=1')
                            ->group('tsa.paper_question_id')
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