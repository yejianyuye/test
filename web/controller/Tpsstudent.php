<?php
namespace app\web\controller;

use   \think\Request;
use   \think\Db;
use   \think\Session;
use   \app\web\controller\Tpslogin;

class Tpsstudent extends \think\Controller
{
    private $ispc = 0;
    //校验登陆
    public function _initialize(){
        $this->ispc = check_wap();
    }
    //短信登陆页面
    public function student_login(){
        if($this->ispc){
            return view('student_login');
        }else{
            return view('pc_student_login');
        }
    }

    //老生登陆页面
    public function ls_login(){
        if($this->ispc){
            return view('ls_login');
        }else{
            return view('pc_ls_login');
        }
    }


    //老生登陆问题
    public function ls_problem(){
        return view('ls_problem');
    }

    //新东方通行证登陆页面()
    public function txz_login(){
        return view('txz_login');
    }


    //注册页面
    public function register(){
       if($this->ispc){
            return view('register');
        }else{
            return view('pc_register');
        }
    }

    //忘记密码
    public function forgetpwd(){
        if($this->ispc){
            return view('forgetpwd');
        }else{
            return view('pc_forgetpwd');
        }
    }

    //测试手机是否已经使用
    public function register_test(){

        $code = Request::instance()->get();
        $count = Db::table('tps_students')->where('tel ='.$code['tel'])->count();

        return $count;

    }

    //公共头部
    public function pc_total_head(){


        return view();

    }

    //注册新用户以及校验
    public function do_register(){


        $code = Request::instance()->get();
        //校验电话号码
        $check_tel = checknum($code['tel']);
        if($check_tel == 0){
            $return_data['error'] = 0;
            $return_data['error_info']='电话号码错误';
            return $return_data;
        }

        $count = Db::table('tps_students')->where('tel ='.$code['tel'])->count();
        if($count == 1){
            $return_data['error'] = 0;
            $return_data['error_info']='电话号码已存在';
            return $return_data;
        }
        //校验密码
        $check_num = checkstrlen($code['permit_psd'],6,20);
        if($check_num == 0){
            $return_data['error'] = 0;
            $return_data['error_info']='密码长度不符合要求';
            return $return_data;
        }

        //校验验证码
        $captcha = new \think\captcha\Captcha();
        $result=$captcha->check($code['yzm']);
        $return_data = array();
        if($result==false){
            $return_data['error'] = 0;
            $return_data['error_info']='验证码错误';
            return $return_data;
        }

        $insertdata['tel'] = $code['tel'];
        $insertdata['permit_psd'] = md5($code['permit_psd']);
        $insertdata['student_num'] = $this->get_student_num();
        $res = Db::table('tps_students')->insert($insertdata);
        if($res){
            $return_data['error'] = 1;
            $this->set_session($insertdata);
            return $return_data;
        }else{
            $return_data['error'] = 0;
            $return_data['error_info']='注册失败';
            return $return_data;
        }

    }


    //显示验证码
    public function show_captcha(){
        //echo 123;die;
        $captcha = new \think\captcha\Captcha();
        //var_dump($captcha);die;
        $captcha->imageW=121;
        $captcha->imageH = 32;  //图片高
        $captcha->fontSize =14;  //字体大小
        $captcha->length   = 4;  //字符数
        $captcha->fontttf = '5.ttf';  //字体
        $captcha->expire = 1800;  //有效期  3分钟
       // $captcha->zhSet = true;  //有效期

        $captcha->useNoise = false;  //不添加杂点
        return $captcha->entry();
    }


    //短息登陆验证
    public function duanxin_login_post(){
        //$code=input('post.captcha');
        $code = Request::instance()->get();
        //$code['tel'];
        $captcha = new \think\captcha\Captcha();
        //校验验证码
        $result=$captcha->check($code['yzm']);
        $return_data = array();
        if($result===false){
            $return_data['error'] = 0;
            $return_data['error_info']='验证码错误';
        }else{
            //校验电话号码
            $check_num = checknum($code['tel']);
            if($check_num == 0){
                $return_data['error'] = 0;
                $return_data['error_info']='电话号码格式错误';

            }else{
                $count = Db::table('tps_students')->where('tel ='.$code['tel'])->find();
                if($count==''){
                    $count['tel']=$code['tel'];
                    //创建唯一学员号
                    $count['student_num'] = $this->get_student_num();
                    $in_res = Db::table('tps_students')->insert($count);
                }
                $this->set_session($count);
                $return_data['error'] = 1;
                $return_data['return_url'] = Session::get('return_url');
            }
        }
        
        
        return $return_data;
    }

    //新东方老生登陆
    public function  laoshen_login_post(){

        //student_num     student_name
        $code = Request::instance()->get();
        $telinfo = Db::table('tps_students')->where('tel ='.$code['tel'])->find();
        if($telinfo==''){
            $return_data['error'] = 0;
            $return_data['error_info']='电话号码不存在';
        }else{

            if($telinfo['student_name'] != $code['student_name']) {
                $return_data['error'] = 0;
                $return_data['error_info'] = '学员手机与姓名不匹配';
            }else if($telinfo['student_name'] == $code['student_name']){
                $return_data['error'] = 1;
                $this->set_session($telinfo);
                $return_data['error'] = 1;
                $return_data['return_url'] = Session::get('return_url');
            }
        }


        return $return_data;
    }

    //新东方通行证登陆
    public function txz_login_post(){

        $code = Request::instance()->get();
       // var_dump($code);die;
        if($code['mail'] == ''){
            $return_data['error'] = 0;
            $return_data['error_info']='输入邮箱';
        } else if($code['permit_psd'] == ''){
            $return_data['error'] = 0;
            $return_data['error_info']='通行证密码不能为空';
        }else{
            $telinfo = Db::table('tps_students')->where('mail',$code['mail'])->find();
            if($telinfo==''){
                $return_data['error'] = 0;
                $return_data['error_info']='通行证不存在';
            }else{
                if($telinfo['permit_psd'] != md5($code['permit_psd']) ) {
                    $return_data['error'] = 0;
                    $return_data['error_info'] = '通行证与密码不匹配';
                }else if($telinfo['permit_psd'] == md5($code['permit_psd'])){
                    $return_data['error'] = 1;
                    $this->set_session($telinfo);
                }
            }
        }

        return $return_data;
    }


    //创建session
    public function set_session($data){
        Session::set('student_num',$data['student_num']);
        Session::set('tel',$data['tel']);
        //$sm_del = Session::delete('tel');
       // $se_del = Session::delete('student_num');
        //return 1;
    }


    //判断号码不能重复
    public function get_student_num(){

        $student_num = rand(1000,9999);
        $count = Db::table('tps_students')->where('student_num',$student_num)->count();
        if($count!=0){
            return $this->get_student_num();
        }else{
            return $student_num;
        }
    }

}