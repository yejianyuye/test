<?php
namespace app\index\controller;

use   \think\Request;
use   \think\Db;
use   \think\Log;
use   \think\Session;
class Paper extends \think\Controller
{

    public $single_title = '新东方新的教育理念是什么';
    public $block_title  = '新东方新的教育理念是什么';
    public $checkbox_title = '新东方所在的城市有';
    public $reading_title = '蓝天碧海间越过地平线，远离尘嚣，一路向东、向东……这次看似寻常的航海之旅其实绝不普通。<span style="text-decoration: underline;"> &nbsp; &nbsp;1 &nbsp; &nbsp;&nbsp;</span>它不仅深化了的新东方<span style="text-decoration: underline;"> &nbsp; &nbsp;2 &nbsp; &nbsp;&nbsp;</span> (更好的你、更大的世界)新的教育理念';
    public $reading_reading_title = '新东方教育';
    public $close_title  =  '蓝天碧海间越过地平线，远离尘嚣，一路向东、向东……这次看似寻常的航海之旅其实绝不普通。它不仅深化了的新东方(更好的你、更大的世界)新的教育理念';
    public $check_A= 'a better you,a bigger world{#}a bigger world,a better you{#}live beautifully, dream passionately, love completely{#}a better life,a better world';
    public $block_answer = '<span contenteditable="false"><input type="text" disabled="disabled" value = "123" style=" width: 90px; border-width: 0 0 1px 0;border-bottom: 1px solid #02a4a6;height: 16px;line-height: 16px;text-align: center;background-color: #fff;"></span>';
   //编辑整份试卷

    public function edit_all_paper(){

        $evaluate_paper_id = Request::instance()->get('evaluate_paper_id');
        $paper_title = Db::table('tps_evaluate_paper')->where('id',$evaluate_paper_id)->find();
        $paper_point = Db::table('tps_paper_point')->where('evaluate_paper_id',$paper_title['id'])->order('point_order desc, id asc')->select();
        $paper_question = '';
        foreach($paper_point as $pk=>$pv){
            $paper_question[$pk] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',0)->order('question_number asc')->select();

            foreach($paper_question[$pk] as $ssk=>$ssv) {

                $paper_question[$pk][$ssk]['contents'] = explode("{#}", $paper_question[$pk][$ssk]['question_content']);
                if ($paper_question[$pk][$ssk]['question_type'] == 1){
                    $paper_question[$pk][$ssk]['question_answer_shuzi'] = $this->question_answer($paper_question[$pk][$ssk]['question_answer']);
                }

                if ($paper_question[$pk][$ssk]['question_type'] == 3){
                    $question_answer = array();
                    $ss = explode(',',$paper_question[$pk][$ssk]['question_answer']);
                    foreach($ss as $sk=>$sv){

                        $question_answer[] = $this->question_answer($sv);
                    }
                    $paper_question[$pk][$ssk]['question_answer_shuzi'] = $question_answer;
                }
                $paper_question[$pk][$ssk]['children'] = Db::table('tps_paper_question')->where('paper_point_id',$paper_point[$pk]['id'])->where('parent_id',$paper_question[$pk][$ssk]['id'])->order('question_number asc')->select();
                foreach($paper_question[$pk][$ssk]['children'] as $sssk=>$sssv){
                    $paper_question[$pk][$ssk]['children'][$sssk]['question_answer_shuzi'] = $this->question_answer($paper_question[$pk][$ssk]['children'][$sssk]['question_answer']);
                    $paper_question[$pk][$ssk]['children'][$sssk]['contents'] =  explode("{#}",$paper_question[$pk][$ssk]['children'][$sssk]['question_content']);
                }
            }
        }
        return $this->fetch('tps_edit_all', [
            'paper_title'  => $paper_title,
            'paper_point' => $paper_point,
            'paper_question' => $paper_question,
            'evaluate_paper_id' => $evaluate_paper_id
        ]);
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

    //添加考点
    public function add_point(){

        $data = Request::instance()->get();
        $paper_point = array(
            'point_title'=>$data['kdmc'],
            'point_grade'=>$data['spfs'],
            'point_order'=>$data['kspx'],
            'evaluate_paper_id'=>$data['evaluate_paper_id'],
        );
        $paper_point['id'] = Db::table('tps_paper_point')->insertGetId($paper_point);
        return $paper_point;

    }

    //编辑考点
    public function edit_point(){

        $data = Request::instance()->get();
        $paper_point = array(
            'point_title'=>$data['kdmc'],
            'point_grade'=>$data['spfs'],
            'point_order'=>$data['kspx'],
            'id'=>$data['point_id'],
        );
        $questionall = Db::table('tps_paper_point')->where('id',$paper_point['id'])->value('question_all');
        $paper_point['all_grade'] = $questionall * $paper_point['point_grade'];
        $res = Db::table('tps_paper_point')->where('id',$paper_point['id'])->update($paper_point);
        if($res == false){
            return false;
        }else{
            return $paper_point;
        }
    }

    //删除考点
    public function del_point(){
        $data = Request::instance()->get();
        Db::startTrans();
        $p_data = Db::table('tps_paper_point')->where('id',$data['paper_point_id'])->delete();
        if($p_data){
            //获取一个考点下面的id 删除后显示

            Db::table('tps_paper_question')->where($data)->delete();
            $res = Db::table('tps_paper_point')->where('evaluate_paper_id',$data['evaluate_paper_id'])->order('point_order desc, id asc')->field('id')->find();
            $res['res'] = 1;
            Db::commit();
            return $res;
        }else{
            $res['res'] = 0;
            Db::rollback();
        }
    }
    //添加考题
    public function add_paper_question(){
        $data = Request::instance()->get();
        $kk = Db::table('tps_paper_question')->where($data)->find();
        if($kk){ return '';}
        if($data['question_type'] == 4 || $data['question_type'] == 5){
            Db::startTrans();
            //$data['question_answer']  为后面的完成整套试卷的编辑做准备
            if($data['question_type'] == 4 ){
                $data['question_title'] = $this->reading_title;
                $data['question_answer'] = 'no answer';
            }else if($data['question_type'] == 5){
                $data['question_title'] = $this->close_title;
                $data['question_answer'] = 'no answer';
            }
            $data['id'] = Db::table('tps_paper_question')->insertGetId($data);
            $data['children']['paper_point_id'] = $data['paper_point_id'];
            $data['children']['evaluate_paper_id'] = $data['evaluate_paper_id'];
            $data['children']['question_type'] = $data['question_type'];
            $data['children']['parent_id'] = $data['id'];
            $data['children']['question_content'] = $this->check_A;
            $data['children']['question_number'] = 1;
            if($data['question_type'] == 4 ){
                $data['children']['question_title'] = $this->reading_reading_title;
            }
            $data['children']['id'] = Db::table('tps_paper_question')->insertGetId($data['children']);
            if($data['id'] =='' || $data['children']['id'] == ''){
                Db::rollback();
                return '';
            }else{
                Db::commit();
            }
        }else if($data['question_type'] == 2){
            Db::startTrans();
            $data['question_title'] = $this->block_title.$this->block_answer;
            $data['question_title2'] = '<p>新东方新的教育理念是什么<span style="padding:1px 50px 1px 50px;border:1px solid #00CD00; width:100px;" class="answer">&nbsp;123</span>&nbsp;</p>';
            $data['id'] = Db::table('tps_paper_question')->insertGetId($data);
            if($data['id'] ==''){
                Db::rollback();
                return '';
            }else{
                Db::commit();
            }
        }else if($data['question_type'] == 1 || $data['question_type'] == 3){
            $data['question_title'] = $this->single_title;
            $data['question_content'] = $this->check_A;
            $data['id'] = Db::table('tps_paper_question')->insertGetId($data);
        }

        //添加完题目后选择当下的考点进行添加分数
        if($data['id']){
            $point_updata = Db::table('tps_paper_point')->where('id',$data['paper_point_id'])->field('id,point_grade,question_all')->find();
            $point_updata['question_all'] = $point_updata['question_all'] + 1;
            $point_updata['all_grade'] = $point_updata['question_all'] * $point_updata['point_grade'];
            $data['all_grade'] = $point_updata['all_grade'];
            Db::table('tps_paper_point')->where('id',$point_updata['id'])->update($point_updata);
            return $data;
        }else{
            return '';
        }
    }

    //添加完形填空 阅读理解下面的题目
    public function add_close_read(){
        $data = Request::instance()->get();
        Db::startTrans();
        if($data['question_type'] == 4 ){
            $data_insert['question_title'] = $this->reading_reading_title;
        }
        $data_insert['paper_point_id'] = $data['paper_point_id'];
        $data_insert['evaluate_paper_id'] = $data['evaluate_paper_id'];
        $data_insert['question_type'] = $data['question_type'];
        $data_insert['question_number'] = $data['question_number'];
        $data_insert['question_content'] = $this->check_A;
        $data_insert['parent_id'] = $data['id'];
        $data_insert['id'] = Db::table('tps_paper_question')->insertGetId($data_insert);
        //添加分数
        if($data_insert['id'] ==''){
            return '';
        }else{
            $point_updata = Db::table('tps_paper_point')->where('id',$data_insert['paper_point_id'])->field('id,point_grade,question_all')->find();
            $point_updata['question_all'] = $point_updata['question_all'] + 1;
            $point_updata['all_grade'] = $point_updata['question_all'] * $point_updata['point_grade'];
            $data_insert['all_grade'] = $point_updata['all_grade'];
            $res = Db::table('tps_paper_point')->where('id',$point_updata['id'])->update($point_updata);
            if($res){
                Db::commit();
                return $data_insert;
            }else{
                Db::rollback();
                return '';
            }
        }
    }

    //1 上移，2 下移，3 删除 （题目）
    public function question_edit(){
        $data = Request::instance()->get();
        //var_dump($data);die;
        $question_data = Db::table('tps_paper_question')->where('id',$data['id'])->find();
        $res['res'] = 1;
        //向上移
        if($data['type'] == 1 || $data['type'] == 2){
            $question_number = $data['type'] == 1 ? $question_data['question_number'] - 1 : $question_data['question_number'] + 1;
            $p_data = array(
                'evaluate_paper_id'=>$question_data['evaluate_paper_id'],
                'paper_point_id'=>$question_data['paper_point_id'],
                'question_number'=>$question_number,
                'parent_id'=>$question_data['parent_id'],
            );
            $question_data_q = Db::table('tps_paper_question')->where($p_data)->find();
            Db::startTrans();
            $res_n = Db::table('tps_paper_question')->where('id',$question_data['id'])->update(['question_number' => $question_data_q['question_number']]);
            $res_q = Db::table('tps_paper_question')->where('id',$question_data_q['id'])->update(['question_number' => $question_data['question_number']]);
            if($res_n ==false || $res_q == false){
                $res['res'] = 2;
                Db::rollback();
            }else{
                $res['res'] = 1;
                Db::commit();
            }
        }else if($data['type'] == 3){
           Db::startTrans();

           Db::table('tps_paper_question')->where('id',$data['id'])->delete();
            $p_data = array(
                'evaluate_paper_id'=>$question_data['evaluate_paper_id'],
                'paper_point_id'=>$question_data['paper_point_id'],
                'parent_id'=>$question_data['parent_id'],
            );
            Db::table('tps_paper_question')
                ->where('question_number','>',$question_data ['question_number'])
                ->where($p_data )
                ->update([
                    'question_number' => ['exp','question_number-1'],
                ]);

            Db::table('tps_paper_question')->where('id',$data['id'])->delete();
           // $question_data[''] =
            $numdel = 1;
            if($question_data['parent_id'] == 0 && ($question_data['question_type'] == 4 || $question_data['question_type'] == 5) ){

                $numdel = Db::table('tps_paper_question')->where('parent_id',$question_data['id'])->count();
                Db::table('tps_paper_question')->where('parent_id',$question_data['id'])->delete();

            }

            //删除题目减总分
            if($data['id']){
                $point_updata = Db::table('tps_paper_point')->where('id',$question_data['paper_point_id'])->field('id,point_grade,question_all')->find();
                $point_updata['question_all'] = $point_updata['question_all'] - $numdel;
                $point_updata['all_grade'] = $point_updata['question_all'] * $point_updata['point_grade'];
                $ress = Db::table('tps_paper_point')->where('id',$point_updata['id'])->update($point_updata);
                if($ress ==false){
                    $res['res'] = 2;
                    Db::rollback();
                }else{
                    $res['res'] = 1;
                    Db::commit();
                }
                $res['all_grade'] = $point_updata['all_grade'];
                $res['point_id'] = $question_data['paper_point_id'];
            }

        }
        return $res;
    }

    //获取题目信息
    public function get_question(){
        $data = Request::instance()->get();
        $res = Db::table('tps_paper_question')->where('id',$data['id'])->find();
        return $res;
    }

    //编辑题目
    public function edit_question(){
        $data = Request::instance()->param();
        $datas = array();
        $isok_answer = Db::table('tps_paper_question')->where('id',$data['id'])->value('question_answer');
        //var_dump($isok_answer);die;
        if($data['type'] ==1 || $data['type'] ==3 || $data['type'] ==6){
            $data['content'] = explode('</p>',substr($data['content'],0,strlen($data['content'])-4));
            //$data['content'] = array_pop($data['content']);
            $arr = '';

            foreach($data['content'] as $ck=>$cv){
                if($ck == 0){
                    $datas['question_title'] = substr($data['content'][$ck],3);
                }else if($data['content'][$ck] == '<p><br/>'){

                }else{
                    $arr .=substr($data['content'][$ck],3).'{#}';
                }
            }
            $datas['question_content'] = substr($arr,0,strlen($arr)-3);

            //var_dump($datas);die;

            $res = Db::table('tps_paper_question')->where('id',$data['id'])->update($datas);
            $datas['isok_answer'] = $isok_answer;

        }else if($data['type'] == 2){
           // var_dump($data['content']);
            $ss = explode('<span style="padding:1px 50px 1px 50px;border:1px solid #00CD00; width:100px;" class="answer">',$data['content']);
            $datas['question_title2'] = $data['content'];
          //  var_dump($ss);die;
            $timu = $ss[0];
            $timu2 = $ss[0];
            $answers = '';
            for ($x=1; $x<count($ss); $x++) {

                $answer = substr(explode('</span>',$ss[$x])[0],6);
                $answers .= '{#}'.$answer;
                $answerp = '<span contenteditable="false"><input type="text" disabled="disabled" value = ';

                $answerp = $answerp.'"'.$answer.'"';
                $answerp = $answerp.' style=" width: 90px; border-width: 0 0 1px 0;border-bottom: 1px solid #02a4a6;height: 16px;line-height: 16px;text-align: center;background-color: #fff;"></span>';

                $answerp2 = '<span contenteditable="false"><input type="text" onchange="xuanze.num('.$data['id'].',2);" class="tkxz tiankong'.$data['id'].'" th = "'.$data['id'].'"';
                $answerp2 = $answerp2.' style="width: 90px; border-width: 0 0 1px 0;border-bottom: 1px solid #02a4a6;height: 16px;line-height: 16px;text-align: center;background-color: #fff;"></span>';

                $timu2 = $timu2.$answerp2;
                $timu = $timu.$answerp;

                $answer_cont[$x] = substr(explode('</span>',$ss[$x])[1],6);
                $timu =$timu.$answer_cont[$x];
                $timu2 =$timu2.$answer_cont[$x];
            }

            $datas['question_title'] = $timu;
            $datas['question_title3'] = $timu2;
            $datas['question_answer'] = substr($answers,3);
            $res = Db::table('tps_paper_question')->where('id',$data['id'])->update($datas);
        }else if($data['type'] == 4 ||$data['type'] == 5){
            $datas['question_title'] = substr($data['content'],3,strlen($data['content'])-7);
            $res = Db::table('tps_paper_question')->where('id',$data['id'])->update($datas);
        }else if($data['type'] ==7){
            $data['content'] = explode('</p>',substr($data['content'],0,strlen($data['content'])-4));
            $arr = '';

            foreach($data['content'] as $ck=>$cv){
                if($data['content'][$ck] == '<p><br/>'){

                }else{
                    $arr .=substr($data['content'][$ck],3).'{#}';
                }
            }
            $datas['question_content'] = substr($arr,0,strlen($arr)-3);

            $res = Db::table('tps_paper_question')->where('id',$data['id'])->update($datas);
            $datas['isok_answer'] = $isok_answer;
        }



        if($res){
            return $datas;
        }else{
            return '';
        }

    }

    //编辑答案
    public function edit_answer(){
        $data = Request::instance()->get();
       // var_dump($data);die;
        if($data['type'] == 1){
            $updata['question_answer'] = $data['question_answer'];
            $res = Db::table('tps_paper_question')->where('id',$data['id'])->update($updata);

        }else if($data['type'] == 2){

            $updata['question_answer'] = implode(',',$data['question_answer']);
            $res = Db::table('tps_paper_question')->where('id',$data['id'])->update($updata);
        }

        return $res;

    }

    //完成整套试卷编辑  发布前准备
    public function do_all_paper(){
        $data = Request::instance()->get();
        //$evaluate_paper_id = $data['evaluate_paper_id'];
       // $paper_question = Db::table('tps_paper_question')->field('paper_point_id,question_number,question_answer,parent_id')->where('evaluate_paper_id',$data['evaluate_paper_id'])->select();





        $paper_question = Db::table('tps_paper_question')
            ->alias('tpq')
            ->field('tpq.paper_point_id,tpq.question_number,tpq.question_answer,tpq.parent_id,tpq.question_type,p.point_title')
            ->join('tps_paper_point p','p.id= tpq.paper_point_id')
            ->where('tpq.evaluate_paper_id ='.$data['evaluate_paper_id'])
            ->select();

        if($paper_question == ''){
            return '';
        }
        $res = '';
        foreach($paper_question as $pk=>$pv){
            if($paper_question[$pk]['question_answer'] == '') {

                // $paper_question = Db::table('tps_paper_question')->field('paper_point_id,question_number,question_grade,question_answer,parent_id')->where('evaluate_id',$data['evaluate_paper_id'])->select();
                if ($paper_question[$pk]['parent_id']==0 && $paper_question[$pk]['question_type'] != 4 && $paper_question[$pk]['question_type'] != 5){

                    if ($paper_question[$pk]['parent_id'] == 0) {
                            //  $point_title = Db::table('tps_paper_point')->where('id',$paper_question[$pk]['paper_point_id'])->value('point_title');
                            $res .= '考点 《' . $paper_question[$pk]['point_title'] . '》,第' . $paper_question[$pk]['question_number'] . '题,答案未填写<br>';
                    } else {
                            // $point_title = Db::table('tps_paper_point')->where('id',$paper_question[$pk]['paper_point_id'])->value('point_title');
                            $paper_questions = Db::table('tps_paper_question')->field('question_number')->where('id', $paper_question[$pk]['parent_id'])->find();

                            $res .= '考点 《' . $paper_question[$pk]['point_title'] . '》,第' . $paper_questions['question_number'] . '题,第' . $paper_question[$pk]['question_number'] . '小题,答案未填写<br>';
                    }
                }
            }
        if($res==''){
            $ss['paper_type'] = 1;
            $ss['edit_time'] = date('Y-m-d H:i:s');
            //添加手机端页面编号以及试卷上面试题的题号
            $paper_questionl = Db::table('tps_paper_question')
                ->alias('tpq')
                ->field('tpq.question_type,tpq.id')
                ->join('tps_paper_point p','p.id= tpq.paper_point_id')
                ->where('tpq.evaluate_paper_id ='.$data['evaluate_paper_id'] .' and  tpq.parent_id = 0')
                ->order('p.point_order asc,p.id asc,tpq.question_number asc')
                ->select();

            foreach($paper_questionl as $pl=>$pv){
                $l = $pl+1;
                Db::table('tps_paper_question')->where('id', $paper_questionl[$pl]['id'])->update(['question_page' => $l]);

                if($paper_questionl[$pl]['question_type'] == 4 || $paper_questionl[$pl]['question_type'] == 5){

                    Db::table('tps_paper_question')->where('parent_id', $paper_questionl[$pl]['id'])->update(['question_page' => $l]);

                }
            }

        }
            Db::table('tps_evaluate_paper')->where('id='.$data['evaluate_paper_id'])->update($ss);
        }
        return $res;
    }


}