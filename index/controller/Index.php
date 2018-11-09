<?php
namespace app\index\controller;

use   \think\Request;
use   \think\Db;
use   \think\Log;
use   \think\Session;
use   \app\index\controller\Cologin;
use   \app\common\controller\Tpsinfo;
class Index extends cologin
{

    private $admin_name = "叶建宇";
   // private $admin_tel = Session::get('tps_zuid');

    //显示主页面
    public function index(){
        //var_dump('password ='.Session::get('password').' and id='.Session::get('tps_adminid'));die;
        $res = Db::table('tps_user')->where('password ='.'\''.Session::get('password').'\''.' and id='.Session::get('tps_adminid'))->find();
        return view('index',[
            'admin_info'=>$res,
        ]);
    }

    //获取试卷的状态以及一些信息
    private function get_class($evaluate_paper){
        foreach($evaluate_paper as $ek=>$ev){
            $evaluate_paper[$ek]['xueke_name'] = Db::table('tps_paper_class')->where('id',$ev['xueke'])->value('name');
            $evaluate_paper[$ek]['nianji_name'] = Db::table('tps_paper_class')->where('id',$ev['nianji'])->value('name');
            $evaluate_paper[$ek]['xueduan_name'] = Db::table('tps_paper_class')->where('id',$ev['xueduan'])->value('name');
            $evaluate_paper[$ek]['type_name'] = Db::table('tps_paper_class')->where('id',$ev['type'])->value('name');
            if($ev['status'] == 1){
                $evaluate_paper[$ek]['type_status'] = '未开始';
            }else if($ev['status'] == 2){
                $evaluate_paper[$ek]['type_status'] = '进行中';
            }else if($ev['status'] == 3){
                $evaluate_paper[$ek]['type_status'] = '已结束';
            }
            $evaluate_paper[$ek]['edit_time'] = substr($evaluate_paper[$ek]['edit_time'],0,strlen($evaluate_paper[$ek]['edit_time'])-3);
        }
        return $evaluate_paper;
    }

    //获取分类名称
    public function get_class_name($id){
        $class_name = Db::table('tps_paper_class')->where('id',$id)->value('name');
        return $class_name;
    }

    //获取问卷调查
    private function get_paper_class($paper_data){
        $res = Db::table('tps_paper_class')->where($paper_data)->select();
        return $res;
    }

    //获取学段下面的年级
    public function get_paper_children(){
        $data = Request::instance()->get();
        $search['parent_id'] = $data['id'];
        $res = Db::table('tps_paper_class')->where($search)->select();
        return $res;
    }

    //获取学段下面的年级
    public function get_paper_children1($id){
        $search['parent_id'] = $id;
        $res = Db::table('tps_paper_class')->where($search)->select();
        return $res;
    }

    //二维码扫描
    public function chengji(){
       // $res = Db::table('tps_user')->where('zu_id ='.)->select();
        return view();
    }
    
    //二维码扫描
    public function guanliyuan(){
        $res = Db::table('tps_user')->where('zu_id ='.Session::get('tps_zuid'))->select();
        return view('guanliyuan',[
            'ss'=>$res,
        ]);
    }

    //测评试卷页面
    public function ceping(){
        $data = Request::instance()->get();
        //var_dump($data);die;
        $page = $data['page'] ? $data['page'] : 1;
        $datas = $this->search_tj($data);
        $evaluate_paper = Db::table('tps_evaluate_paper')->where($datas['map'])->page($page,2)->select();
        $count = Db::table('tps_evaluate_paper')->where($datas['map'])->count();
        $pageCount=ceil($count/2);
        $evaluate_papers   = $this->get_class($evaluate_paper);
        //搜索学段,如小学，初中，高中
        $paper_data['type'] = 0;
        $paper_data['parent_id'] = 0;
        $paper_class = $this->get_paper_class($paper_data);
        //搜索学科
        $paper_data1['type'] = 1;
        $paper_class1 = $this->get_paper_class($paper_data1);
        $url = 'ceping?status='.$datas['map']['status'].'&evaluate_id='.$data['evaluate_id'];
        $str = $this->myde_write($pageCount,$page,$url,'');
        return view('ceping', [
            'evaluate_papers'  => $evaluate_papers,
            'paper_class'  => $paper_class,
            'paper_class1'  => $paper_class1,
            'evaluate_id'  => $data['evaluate_id'],//用于添加试卷
            'search_datas'  => $datas['search_datas'],
            'count'  => $count,
            'str'  => $str,
        ]);
    }

    //在某个测试下面添加试卷
    public function add_epp(){
        $data = Request::instance()->get();

        $paper_data['type'] = 0;
        $paper_data['parent_id'] = 0;
        $paper_class = $this->get_paper_class($paper_data);
        $paper_data1['type'] = 1;
        $paper_class1 = $this->get_paper_class($paper_data1);
        return view('add_epp', [
            'paper_class'  => $paper_class,
            'paper_class1'  => $paper_class1,
            'evaluate_id'  => $data['evaluate_id'],
        ]);
    }

    //添加测评
    public function add_ep(){
        $data = Request::instance()->get();
        if($data['miaoshu'] ==''){
            unset($data['miaoshu']);
        }
        $data['edit_time'] = date("Y-m-d H:i:s", time());
        $data['tel_allow'] = Session::get('tps_zuid');

        //加一重id对试卷的校验
        $res['souseid'] = $data['add_time'] = time();
        $res['id'] = Db::table('tps_evaluate_paper')->insertGetId($data);
        return  $res;
    }


    //在某个测试下面编辑试卷
    public function edit_epp(){
        $data = Request::instance()->get();
        $paper_data['type'] = 0;
        $paper_data['parent_id'] = 0;
        $paper_class = $this->get_paper_class($paper_data);
        $paper_data1['type'] = 1;
        $paper_class1 = $this->get_paper_class($paper_data1);

        $paper_info = Db::table('tps_evaluate_paper')->field('xueke,nianji,xueduan,add_time,id,paper_name,miaoshu')->where('id ='.$data['evaluate_paper_id'].' and add_time='.$data['souseid'])->find();
        $paper_info['xueke_name'] = Db::table('tps_paper_class')->where('id = '.$paper_info['xueke'])->value('name');
        $paper_info['nianji_name'] = Db::table('tps_paper_class')->where('id = '.$paper_info['nianji'])->value('name');
        $paper_info['xueduan_name'] = Db::table('tps_paper_class')->where('id = '.$paper_info['xueduan'])->value('name');

        $paper_info['evaluate_paper_id'] = $data['evaluate_paper_id'];
        //$paper_info['souseid'] = $data['souseid'];
       // $paper_info['edit_id'] = Session::get('tps_adminid');
        return view('edit_epp', [
            'paper_class'  => $paper_class,
            'paper_class1'  => $paper_class1,
            'paper_info'  => $paper_info,
            'paper_data'  => $data,
        ]);
    }

    //完成学科，学段，年级的信息编辑
    public function finish_edit(){
        $data = Request::instance()->get();
        $data['paper_type'] = 0;
        $data['edit_id'] = Session::get('tps_adminid');
        $data['edit_time'] =date("Y-m-d H:i:s",time());
        return Db::table('tps_evaluate_paper')->where('id ='.$data['id']. ' and add_time ='.$data['add_time'])->update($data);
        
    }

    //编辑测评
    public function edit_ep(){
        $data = Request::instance()->get();
        if($data['miaoshu'] ==''){
            unset($data['miaoshu']);
        }
        $data['edit_time'] = date("Y-m-d H:i:s", time());

        $res = Db::table('tps_evaluate_paper')->insertGetId($data);
        return  $res;
    }

    //删除测评试卷
    public function del_paper(){
        $data = Request::instance()->get();
        Db::startTrans();

        $paper_id = Db::table('tps_evaluate_paper')->where('id',$data['paper_id'])->value('status');
        if($paper_id == 1){
            $e_res = Db::table('tps_evaluate_paper')->where('id',$data['paper_id'])->delete();

            $p_data = Db::table('tps_paper_point')->where('evaluate_paper_id',$data['paper_id'])->delete();
            $q_data = Db::table('tps_paper_question')->where('evaluate_paper_id',$data['paper_id'])->delete();
            if($e_res){
                Db::commit();
                return  1;
            }else{
                Db::rollback();
                return  0;
            }
        }else{
            return  0;
        }
    }

    //预览试卷
    public function paper_preview(){
        //预约id
        $get_test_paper_id = 18;
        $search_data['id'] = $get_test_paper_id;
        $appointment_info = Db::table('tps_appointment')->field('status,time_allow,evaluate_paper_id')->where($search_data)->find();
            if($appointment_info['status'] == 2){
                echo '该测评只能测评一次，考生已完成该测评';die;
            }else{
                $tpsinfo = New Tpsinfo();
                $paper_question_all = $tpsinfo->get_paper($appointment_info['evaluate_paper_id'],1);
                //echo '<pre>';print_r($paper_question_all);echo '<pre>';die;
                return view('paper/paper_preview', [
                    'id'  => $get_test_paper_id,
                    'paper_question'  => $paper_question_all['paper_question'],
                    'count'  => $paper_question_all['count'],
                    'pageall'  => $paper_question_all['pageall'],
                    'time_allow'  => $appointment_info['time_allow'],
                    'evaluate_paper_id'  => $appointment_info['evaluate_paper_id'],
                ]);
            }
    }

    //发布试卷
    public function fabu_paper(){
        $data = Request::instance()->get();

        $tep = Db::table('tps_evaluate_paper')->where("id =".$data['evaluate_paper_id']." and paper_type= 1 and add_time =".$data['souseid'])->find();
        $tep['cp_begintime'] = str_replace(" ","T",$tep['cp_begintime']);
        $tep['cp_endtime'] = str_replace(" ","T",$tep['cp_endtime']);
        $tep['fb_time'] = str_replace(" ","T",$tep['fb_time']);
        $tep['xueduan_name'] = Db::table('tps_paper_class')->where('id',$tep['xueduan'])->value('name');
        $tep['xueke_name'] = Db::table('tps_paper_class')->where('id',$tep['xueke'])->value('name');
        $tep['nianji_name'] = Db::table('tps_paper_class')->where('id',$tep['nianji'])->value('name');
        //var_dump($tep);die;

        $tev = Db::table('tps_evaluate_tuijian')->where('evaluate_paper_id',$data['evaluate_paper_id'])->select();

       // $count = Db::table('tps_evaluate_tuijian')->where('evaluate_paper_id',$data['evaluate_paper_id'])->count();

        $first_id = Db::table('tps_evaluate_tuijian')->where('evaluate_paper_id',$data['evaluate_paper_id'])->find();
        if($first_id==''){
            $first_id['id'] = 0;
        }

        foreach($tev as $tk=>$tv){
            
            $tev[$tk]['tj_kcarray'] = explode(';',$tev[$tk]['tj_kc']);
            $tev[$tk]['tj_kcbharray'] = explode(';',$tev[$tk]['tj_kcbh']);
            $tev[$tk]['tj_kcdzarray'] = explode(';',$tev[$tk]['tj_kcdz']);

        }  
        //var_dump($tev);
        return view('fabu', [
            'evaluate_paper_id'  => $data['evaluate_paper_id'],
            'tep'  => $tep,
            'tev'  => $tev,
            'first_id'  => $first_id['id'],
        ]);
    }

    //添加分数段推荐
    public function add_evaluate_tuijian(){

            $data = Request::instance()->get();
            $minScore = $data['minScore'];
            $maxScore = $data['maxScore'];
            $resi = Db::table('tps_evaluate_tuijian')->where('minScore <'.$minScore .' and maxScore > '.$minScore .' and evaluate_paper_id = '.$data['evaluate_paper_id'] )->count();

            $resa = Db::table('tps_evaluate_tuijian')->where('maxScore <'.$maxScore .' and maxScore > '.$maxScore .' and evaluate_paper_id = '.$data['evaluate_paper_id'] )->count();

            $resd = Db::table('tps_evaluate_tuijian')->where('minScore >'.$minScore .' and maxScore < '.$maxScore .' and evaluate_paper_id = '.$data['evaluate_paper_id'])->count();

            $resdy = Db::table('tps_evaluate_tuijian')->where('( minScore ='.$minScore .' or maxScore = '.$maxScore .'  or maxScore = '.$maxScore .'  or maxScore = '.$maxScore .' )and evaluate_paper_id = '.$data['evaluate_paper_id'])->count(); 
            if($resi || $resa || $resd || $resdy){
                return 0;
            }
            
            $res = Db::table('tps_evaluate_tuijian')->insertGetId($data);
            return  $res;
    }

    //发布测评报告
    public function add_evaluate_fabu(){

            $data = Request::instance()->get();
           // 测评时长
            $fabu['cp_sc'] = $data['cp_sc']=='true' ? 1 :0;
            if($fabu['cp_sc'] ==1){
                $fabu['cp_time'] = $data['cp_time'];
            }
            //测评时间
            $fabu['cp_sj'] = $data['cp_sj']=='true' ? 1 :0;
            if($fabu['cp_sj'] ==1){
                if($data['publishEndTime'] <=$data['publishBeginTime']){
                        return 2;
                }
                $fabu['cp_begintime'] = $data['publishBeginTime'];
                $fabu['cp_endtime'] = $data['publishEndTime'];
            }
            //发布报告时间
            $fabu['fb_sj'] = $data['fb_sj']=='true' ? 1 :0;
            if($fabu['fb_sj'] ==1){
                $fabu['fb_time'] = $data['publishTime'];
            }
            //支持预约
            $fabu['is_appointment'] = $data['is_appointment']=='true' ? 1 : 0;
            //支持暂停
            $fabu['stop'] = $data['zczt']=='true' ? 1 : 0;
            //试题乱序展示
            $fabu['luanxu'] = $data['stlxzs']=='true' ? 1 : 0;
            //推荐课程
            // var_dump($data);
            $fabu['tuijian_kc'] = $data['tjkc']=='true' ? 1 : 0;

            $fabu['fbsj_time'] = date("Y-m-d H:i:s", time());

            Db::startTrans();
            $tj_res = 0;
            if($fabu['tuijian_kc'] == 1){
                //删除之前paper下面的课程
                Db::table('tps_evaluate_tuijian')->where('evaluate_paper_id',$data['evaluate_paper_id'])->delete();
                //根据推荐分数段--》推荐课程
                $insert['evaluate_paper_id'] = $data['evaluate_paper_id'];
                foreach($data['minScore'] as $dk=>$dv){

                    $insert['minScore'] = $data['minScore'][$dk];
                    $insert['maxScore'] = $data['maxScore'][$dk];
                    $insert['dengji_name'] = $data['dengji_name'][$dk];
                    $insert['dengji'] = $data['dengji'][$dk];
                    $insert['tj_kc']=implode(';',$data['kec'][$dk]);
                    $insert['tj_kcbh']=implode(';',$data['kcnum'][$dk]);
                    $insert['tj_kcdz']=implode(';',$data['kcurl'][$dk]);
                    $tj_res=Db::table('tps_evaluate_tuijian')->insert($insert);
                }
            }

            $data['status'] = 2;
            //var_dump($data);die;
            $tep = Db::table('tps_evaluate_paper')->where('id ='.$data['evaluate_paper_id'].' and status=1')->update($fabu);
            if($tj_res && $tep){
                Db::commit();
                return $data=1;
            }else{
                Db::rollback();
                return $data=0; 
            }
            
    }


    //整份试卷的测评情况=>整体测评
    public function paper_tps(){

        return view();

    }


    //删除分数段推荐
    public function del_evaluate_tuijian(){

            $data = Request::instance()->get();
            $res = Db::table('tps_evaluate_tuijian')->where('id ='.$data['id'])->delete();
            return  $res;
    }

    //发布考卷后撤销
    public function chexiao_paper(){
        $data = Request::instance()->get();


        $paper_id = Db::table('tps_evaluate_paper')->where('id',$data['paper_id'])->value('status');
        if($paper_id == 2){
            $e_res = Db::table('tps_evaluate_paper')->where('id',$data['paper_id'])->update(['status' => 1]);

           return $e_res;
        }
    }

    //发布考卷后下架
    public function xiajia_paper(){
        $data = Request::instance()->get();

        $paper_id = Db::table('tps_evaluate_paper')->where('id',$data['paper_id'])->value('status');
        if($paper_id == 2){
            $e_res = Db::table('tps_evaluate_paper')->where('id',$data['paper_id'])->update(['status' => 3]);
            return $e_res;
        }
    }



    //php 生成测评二维码
    public function phpqrcode(){

        require_once   $_SERVER['DOCUMENT_ROOT'].'/vendor/phpqrcode/phpqrcode.php';
       // require_once   $_SERVER['DOCUMENT_ROOT'].'/vendor/PHPExcel/classes/PHPExcel.php';
       // var_dump($_SERVER['DOCUMENT_ROOT'].'vendor/phpqrcode/phpqrcode.php');die;
        $data = Request::instance()->get();
        $url = 'http://192.168.68.49:8088/index.php/web/Tps/testpaper_login?evaluate_paper_id='.$data['paper_id'];
        $value = $url;                  //二维码内容

        $errorCorrectionLevel = 'L';    //容错级别
        $matrixPointSize = 5;           //生成图片大小
        $erweimaname = time();
        //生成二维码图片
        $filename =  $_SERVER['DOCUMENT_ROOT'].'public/upload/erweima/'.$erweimaname.'.png';
        \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
        $QR = $filename;                //已经生成的原始二维码图片文件
        $QR = imagecreatefromstring(file_get_contents($QR));
        //输出图片
        imagepng($QR, 'qrcode.png');
        imagedestroy($QR);
        return '<img src="/public/upload/erweima/'.$erweimaname.'.png" alt="使用微信扫描支付">';
    }
    //搜索条件处理
    public function search_tj($data){
        $map['evaluate_id']  = 5;
        $search_datas = array("paper_name"=>"","xueduan"=>"","nianji"=>"","status"=>"","tel"=>"","student_name"=>"","xueke"=>"","xueke_name"=>"","xueduan_name"=>"","nianji_name"=>"","status_name"=>"","nianji_search"=>"","status"=>"");
        if(array_key_exists('paperName',$data) && $data['paperName']!= '' ){
            $map['paper_name']  = ['like','%'.$data['paperName'].'%'];
            $search_datas['paper_name']  = $data['paperName'];
        }
        //学段

        if(array_key_exists('paperStage',$data) && $data['paperStage']!= '' ){
            $map['xueduan']  = $data['paperStage'];
            $search_datas['xueduan']  = $data['paperStage'];
            $search_datas['xueduan_name']  = $this->get_class_name($search_datas['xueduan']);
            $search_datas['nianji_search'] = $this->get_paper_children1($map['xueduan']);
        }

        //年级
        if(array_key_exists('paperGrade',$data) && $data['paperGrade']!= '' ){
            $map['nianji']  = $data['paperGrade'];
            $search_datas['nianji']  = $data['paperGrade'];
            $search_datas['nianji_name']  = $this->get_class_name($map['nianji']);
        }
        if(array_key_exists('paperSubject',$data) && $data['paperSubject'] != '' ){
            $map['xueke']  = $data['paperSubject'];
            $search_datas['xueke']  = $data['paperSubject'];
            $search_datas['xueke_name']  = $this->get_class_name($map['xueke']);
        }

        if(array_key_exists('paperStatus',$data) && $data['paperStatus'] != '' ){
            $map['status']  = $data['paperStatus'];
            $search_datas['status']  = $data['paperStatus'];
        }

        if(array_key_exists('userName',$data) && $data['userName'] != '' ){
            $map['student_name']  = ['like','%'.$data['userName'].'%'];
            $search_datas['student_name']  = $data['userName'];
        }

        if(array_key_exists('userTel',$data) && $data['userTel'] != '' ){
            $map['tel']  = $data['userTel'];
            $search_datas['tel']  = $data['userTel'];
        }
        //发布试卷的状态
        if(array_key_exists('status',$data) && $data['status'] != '' ){
            $map['status']  = $data['status'];
            $search_datas['status']  = $data['status'];
        }


        //试卷种类
        if(array_key_exists('evaluate_id',$data) && $data['evaluate_id']!= '' ){
            $map['evaluate_id']  = $data['evaluate_id'];
            $search_datas['evaluate_id'] = $data['evaluate_id'];
        }

        $datas['map'] = $map;
        $datas['search_datas'] = $search_datas;
        return $datas;
    }

    //搜索条件处理
    public function search_tj1($data){
       // $bskcsz = Db::table('tps_evaluate_paper')->where('status',2)->select();
        $search_datas = array("evaluate_paper_id"=>"","nianji"=>"","dq"=>"","xq"=>"","paper_name"=>"","nianji_name"=>"年级","dq_name"=>"","xq_name"=>"");
        $map = array();
        //考点搜索考场
        if(array_key_exists('evaluate_paper_id',$data) && $data['evaluate_paper_id']!= '' ){
            $map['evaluate_paper_id']  = $data['evaluate_paper_id'];
            //$bskcsz = Db::table('tps_evaluate_paper')->where('id',$data['evaluate_paper_id'])->value('paper_name');
            $search_datas['paper_name']  = Db::table('tps_evaluate_paper')->where('id',$data['evaluate_paper_id'])->value('paper_name');
            $search_datas['evaluate_paper_id']  = $data['evaluate_paper_id'];
        }
        //年级搜索考场
        if(array_key_exists('nianji',$data) && $data['nianji']!= '' ){
            $map['nianji']  = $data['nianji'];
            $search_datas['nianji']  = $data['nianji'];
            switch ($data['nianji'])
            {
                case 1:
                    $search_datas['nianji_name'] = '一年级';
                    break;
                case 2:
                    $search_datas['nianji_name'] = '二年级';
                    break;
                case 3:
                    $search_datas['nianji_name'] = '三年级';
                    break;
                case 4:
                    $search_datas['nianji_name'] = '四年级';
                    break;
                case 3:
                    $search_datas['nianji_name'] = '五年级';
                    break;
                case 3:
                    $search_datas['nianji_name'] = '六年级';
                    break;
                default:
                    $search_datas['nianji_name'] = '年级';
            }

            //$search_datas['xueduan_name']  = $this->get_class_name($search_datas['xueduan']);
        }
        //所在区
        if(array_key_exists('dq',$data) && $data['dq']!= '' ){
            $map['dq']  = $data['dq'];
            $search_datas['dq']  = $data['dq'];
            $search_datas['dq_name']  = $this->get_dq_name($map['dq']);
            $search_datas['xq_list']  = $this->get_dq_children1($map['dq']);
        }

        //选择校区
        if(array_key_exists('xq',$data) && $data['xq']!= '' ){
            $map['xq']  = $data['xq'];
            $search_datas['xq']  = $data['xq'];
            $search_datas['xq_name']  = $this->get_dq_name($map['xq']);
        }

        $datas['map'] = $map;
        $datas['search_datas'] = $search_datas;
        return $datas;
    }


    //上一页
    private function myde_prev($page,$url,$paper_num_detail) {
        //var_dump($url);die;
        if ($page != 0) {
            return '<li class="pageup noClick"><a url = "'.$url.'"  paper_detail = "'.$paper_num_detail.'"   href="javascript:void(0);"  onclick="changePageNo('.$page.',this)"> 上一页</a></li>';
        } else {
            return '<li class="pageup noClick"><a href="javascript:void(0);"> 上一页</a></li>';
        }
    }

    //下一页
    private function myde_next($pageCount,$page,$url,$paper_num_detail) {
        if ($pageCount+1 != $page) {
            return '<li class="pagedown"><a url = "'.$url.'"  paper_detail = "'.$paper_num_detail.'" href="javascript:void(0);"  onclick="changePageNo('.$page.',this)">下一页 </a></li>';
        } else {
            return '<li class="pagedown"><a href="javascript:void(0);">下一页 </a></li>';
        }
    }
    //分页
    public function myde_write($pageCount,$page,$url,$paper_num_detail){
        $str = '';
        $str.=$this->myde_prev($page-1,$url,$paper_num_detail);

        if ($page-1 > 1) {
            $str.='<li><span class="dotStyle">...</span></li>';
        }
        if($page-1<1){
            $page_pre = 1;
        }else{
            $page_pre = $page-1;
        }
        if($page+1>$pageCount){
            $page_nex = $pageCount;
        }else{
            $page_nex = $page+1;
        }
        for($i = $page_pre; $i <= $page_nex; $i++){
            if($page == $i){
                $str .= '<li><a class="pageCurrent" href="javascript:void(0);">'.$i.'</a></li>';
            }else{
                if($paper_num_detail){
                    $str .= '<li><a  href="javascript:void(0);" url = "'.$url.'"  paper_detail = "'.$paper_num_detail.'"  onclick="changePageNo('.$i.',this)">'.$i.'</a></li>';
                }else{
                    $str .= '<li><a  href="javascript:void(0);" url = "'.$url.'"  onclick="changePageNo('.$i.',this)">'.$i.'</a></li>';
                }
            }
        }

        if ($page_nex < $pageCount) {
            $str.='<li><span class="dotStyle">...</span></li>';
        }

        $str.=$this->myde_next($pageCount,$page+1,$url,$paper_num_detail);
        $str.='<li>跳转到<input type="text" class="input_num" onkeyup="value=value.replace(/[^\d]/g,\'\')" onblur="value=value.replace(/[^\d]/g,\'\')"><ac href="javascript:void(0);"  id = "seaech"  url = "'.$url.'"  paper_detail = "'.$paper_num_detail.'"  onclick="enterNumber(this);" class="jumpToPage">确定</ac></li>';


        return $str;
    }

    //测评统计
    public function cptj(){
        $data = Request::instance()->get();
        $page = $data['page'] ? $data['page'] : 1;
        $datas = $this->search_tj($data);
        $evaluate_paper = Db::table('tps_evaluate_paper')->where($datas['map'])->page($page,1)->select();
        $count = Db::table('tps_evaluate_paper')->where($datas['map'])->count();
        $pageCount=ceil($count/1);
        $evaluate_papers   = $this->get_class($evaluate_paper);
        $paper_data['type'] = 0;
        $paper_data['parent_id'] = 0;
        $paper_class = $this->get_paper_class($paper_data);
        $paper_data1['type'] = 1;
        $paper_class1 = $this->get_paper_class($paper_data1);
        $url = 'cptj?';
        $str = $this->myde_write($pageCount,$page,$url,'');
        return view('cptj', [
            'evaluate_papers'  => $evaluate_papers,
            'paper_class'  => $paper_class,
            'paper_class1'  => $paper_class1,
            'count'  => $count,
            'str'  => $str,
            'search_datas'  => $datas['search_datas'],
        ]);

    }

    //每份测评人数详情
    public function paper_num_detail(){
        $data = Request::instance()->get();
        $page = $data['page'] ? $data['page'] : 1;

        $search_datas = array("student_name"=>"");
        if(array_key_exists('id',$data) && $data['id'] != '' ){

            $map['evaluate_paper_id']  = $data['id'];
        }
        if(array_key_exists('userName',$data) && $data['userName'] != '' ){
            $map['student_name']  = ['like','%'.$data['userName'].'%'];
            $search_datas['student_name'] = $data['userName'];
        }
        $paper_name = Db::table('tps_evaluate_paper')->where('id',$data['id'])->value('paper_name');

        $evaluate_content = Db::table('tps_paper_content')->where($map)->page($page,1)->select();
        $count = Db::table('tps_paper_content')->where($map)->count();
        $pageCount=ceil($count/1);
        $url = 'paper_num_detail?';
        $str = $this->myde_write($pageCount,$page,$url,$data['id']);
        return view('paper_num_detail', [
            'evaluate_content'  => $evaluate_content,
            'count'  => $count,
            'str'  => $str,
            'search_datas'  => $search_datas,
            'paper_name'  => $paper_name,
        ]);
    }

    //阶段测评结果
    public function jdcpjg(){
        $data = Request::instance()->get();
        $page = $data['page'] ? $data['page'] : 1;
        $datas = $this->search_tj($data);
        $evaluate_contents = Db::table('tps_paper_content')
                            ->alias('tc')
                            ->join('tps_evaluate_paper tp','tp.id = tc.evaluate_paper_id')
                            ->where($datas['map'])
                            ->page($page,1)
                            ->select();

        $count = Db::table('tps_paper_content')
            ->alias('tc')
            ->join('tps_evaluate_paper tp','tp.id = tc.evaluate_paper_id')
            ->where($datas['map'])
            ->count();
        $pageCount=ceil($count/1);

        $evaluate_content = $this->get_class($evaluate_contents);
        foreach($evaluate_content as $ek=>$cv){
            $evaluate_content[$ek]['tp_time'] = substr($evaluate_content[$ek]['tp_time'],0,strlen($evaluate_content[$ek]['tp_time'])-3);
            $evaluate_content[$ek]['run_time'] = substr($evaluate_content[$ek]['run_time'],3);
        }
        $paper_data['type'] = 0;
        $paper_data['parent_id'] = 0;
        $paper_class = $this->get_paper_class($paper_data);
        $paper_data1['type'] = 1;
        $paper_class1 = $this->get_paper_class($paper_data1);
        $url = 'jdcpjg?';
        $str = $this->myde_write($pageCount,$page,$url,'');
        return view('jdcpjg', [
            'evaluate_content'  => $evaluate_content,
            'paper_class'  => $paper_class,
            'paper_class1'  => $paper_class1,
            'count'  => $count,
            'str'  => $str,
            'search_datas'  => $datas['search_datas'],
        ]);
    }

    //比赛考场设置
    public function bskcsz(){

        $data = Request::instance()->get();
        $page = $data['page'] ? $data['page'] : 1;
        $datas = $this->search_tj1($data);
        //var_dump($datas);die;
        $kaodian = Db::table('tps_kaodian')->where($datas['map'])->page($page,1)->select();
        //var_dump($kaodian);die;
        $count = Db::table('tps_kaodian')->where($datas['map'])->count();
        $pageCount=ceil($count/1);
        $url = 'bskcsz?';
        $str = $this->myde_write($pageCount,$page,$url,'');
        $bskcsz = Db::table('tps_evaluate_paper')->where('status',2)->select();
        $dq = Db::table('tps_dxq')->where('parent_id',0)->select();
        return view('bskcsz', [
            'bskcsz'  => $bskcsz,
            'dq'  => $dq,
            'search_datas'  => $datas['search_datas'],
            'kaodian'  => $kaodian,
            'str'  => $str,
            'count'  => $count,
        ]);
    }

    //考场位置
    public function bskcwz(){
        $data = Request::instance()->get();
        $page = $data['page'] ? $data['page'] : 1;
        $kc_name = Db::table('tps_kaodian')->where('id',$data['id'])->value('kc_name');
        $kcwz = Db::table('tps_kc_wz')->where('kd_num',$data['id'])->page($page,1)->select();
        $count = Db::table('tps_kc_wz')->where('kd_num',$data['id'])->count();
        $pageCount=ceil($count/1);
        $url = 'bskcwz?';
        $to_url = 'bskcwz?id='.$data['id'].'&page=1';
        $str = $this->myde_write($pageCount,$page,$url,$data['id']);
        return view('bskcwz', [
            'kcwz'  => $kcwz,
            'kc_name'  => $kc_name,
            'str'  => $str,
            'count'  => $count,
            'to_url'  => $to_url,
        ]);
    }



    //根据大区获取校区
    public function get_dq_children(){

        $data = Request::instance()->get();
        $search['parent_id'] = $data['id'];
        $res = Db::table('tps_dxq')->where($search)->select();
        return $res;

    }

    //根据大区获取校区
    public function get_dq_children1($id){
        $search['parent_id'] = $id;
        $res = Db::table('tps_dxq')->where($search)->select();
        return $res;

    }

    //根据校区id获取校区名字
    public function get_dq_name($qy){
        $dq_name= Db::table('tps_dxq')->where('qy',$qy)->value('qy_name');
        return $res = $dq_name ? $dq_name : '';

    }

    //导入考场信息
    public function upload_excel_kc(){
        require_once   $_SERVER['DOCUMENT_ROOT'].'/vendor/PHPExcel/classes/PHPExcel.php';

      // $sh_type = Request::instance()->post();
        if (!empty($_FILES)) {
            $tmp_name = $_FILES ['file_stu'] ['tmp_name'];
            $file_names = explode(".", $_FILES ['file_stu'] ['name'] );
            $file_type = $file_names[count( $file_names )-1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower($file_type)!= "xls" && strtolower($file_type) != "xlsx")
            {
                echo $res = json_encode(array("isok"=>2));
                //$this->error ( '不是Excel文件，重新上传' );
            }
            /*设置上传路径*/
            $savePath =  $_SERVER['DOCUMENT_ROOT'].'public/upload/excel/';
            /*以时间来命名上传的文件*/
            $str = date ( 'Ymdhis' );
            $file_name = $_SERVER['HTTP_HOST'].'/public/upload/excel/' . $str . "." .$file_type ;
            $ss = $savePath.$str."." . $file_type;
            //是否上传成功
            if (!copy($tmp_name,$savePath.$str."." . $file_type))
            {
                echo $res = json_encode(array("isok"=>5));
            }
            //文件名为文件路径和文件名的拼接字符串
            $evaluate_paper_id  = Request::instance()->post('paper_name');

            //分数线判断是否及格
            //$passline = $sh_type == 1 ? $this->passline_cxb : $this->passline_xxwb;
            if($file_type =='xlsx' ){
                $objReader = \PHPExcel_IOFactory::createReader('excel2007');
            }else{
                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
            }
            $objPHPExcel = $objReader->load($ss);//加载文件
            $sheet = $objPHPExcel->getSheet(0);//取得sheet(0)表
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $data['evaluate_paper_id'] = $evaluate_paper_id;
            for($i=2;$i<=$highestRow;$i++)
            {
               // $data['student_name']=(string)$objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                $data['xq_name']= (string)$objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
                //根据校区名称加入数据数据库
                $data['xq']= (string)$objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();

                $xq = Db::table('tps_dxq')->where('qy',$data['xq'])->find();
                if($xq){
                    $dq = Db::table('tps_dxq')->where('qy',$xq['parent_id'])->where('parent_id',0)->find();
                    if($dq){
                        $data['dq'] = $dq['qy'];
                        $data['dq_name'] = $dq['qy_name'];
                    }else{
                        continue;
                    }
                }else{
                    continue;
                }

                $data['student_num']= (string)$objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
                $data['nianji']= (string)$objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
                $data['ks_data']= (string)$objPHPExcel->getActiveSheet()->getCell("F".$i)->getValue();
                $data['kc_name']= (string)$objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
                $data['kc_bh']= (string)$objPHPExcel->getActiveSheet()->getCell("H".$i)->getValue();
                $data['ks_rq']= (string)$objPHPExcel->getActiveSheet()->getCell("I".$i)->getValue();
                $shared = new \PHPExcel_Shared_Date();
                $das = $shared ->ExcelToPHP($data['ks_rq']);
                $data['ks_rq'] = date("Y-m-d",$das);

                // $ss = Db::table('tps_kaodian')->where($data)->find();
                 //if()
                //写死分数线为了快速入库
                $res = Db::table('tps_kaodian')->insert($data);
                if($res){
                    echo $res = json_encode(array("isok"=>1));
                }
            }


        }else{
            echo $res = json_encode(array("isok"=>3));
        }
    }

}