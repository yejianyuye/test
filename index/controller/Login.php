<?php
namespace app\index\controller;
use   \think\Request;
use   \think\Db;
use   \think\Session;

class Login extends \think\Controller
{
    //登陆
    public function login()
    {
        return view('common/login');
    }

    public function do_login()
    {
        $data = Request::instance()->post();
        $res = Db::table('user')->where('username', $data['username'])->find();
        if($res){
            if($res['password'] == md5($data['password'])){
                Session::set('xxwb_admin',$res['id']);
                $this->error(('登录成功！'), url('/index/Grade/bootstrap'));
            }else{
                $this->error(('用户名或者密码错误'), url('/index/Login/xxwb_login'));
            }

        }else{
            $this->error(('用户名不存在'), url('/index/Login/xxwb_login'));
        }
    }

    //登陆
    public function register()
    {
        return view('common/register');
    }

    //ajax  校验电话号码
    public function ajax_check_tel(){
        $data = Request::instance()->get();
        return $this->check_tel($data['tel']);
    }
    public function check_tel($data)
    {
        $data = Request::instance()->get();
        if(checknum($data['tel'])){
            $count = Db::table('tps_user')->where('tel', $data['tel'])->count();
            if($count>0){
                return 0;
            }else{
                return 1;
            }
            
        }else{
            return 2;
        }
    }



    public function do_register()
    {
        $data = Request::instance()->get();
        /*
         *0 电话号码已经注册  2 电话号码格式错误  3 用户名错误  4 密码格式错误 5 二次密码不一致 6 注册用户名失败
        */
        $tel_data =  $this->check_tel($data['tel']);
        if($tel_data != 1){
            return $tel_data;
        }
        //校验密码
        $password_length = strlen($data['password']);
        if($password_length < 6 && $password_length > 20){
            return 4;
        }
        //确认密码
        if($data['password'] !=$data['sure_password'] ){
            return 5;
        }
        $insert_data['username'] = $data['user'];
        $insert_data['password'] = md5($data['password']);
        $insert_data['tel'] = $data['tel'];
        $insert_data['md_tel'] = md5($data['tel']);
        $getId = Db::table('tps_user')->insertGetId($insert_data);
        if($getId){
            Session::set('tps_admin',$getId);
            Session::set('tps_tel',$insert_data['tel']);
            return 1;
        }else{
            return 6;
        }

    }



}