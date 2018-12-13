<?php
namespace app\web\controller;
use think\Controller;
use think\View;
use think\Db;
use   \think\Session;

class Index extends \think\Controller
{
    public function index()
    {

    	$res = Db::table('tps_students')->where('tel ='.Session::get('tel').' and student_num ='.Session::get('student_num'))->find();
    	
    		$res['isok'] = $res ? 1 : 0;
        return view('common/index',[

        	'redata'=>$res,
        ]);
    }
    
       
}
