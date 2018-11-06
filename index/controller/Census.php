<?php
namespace app\index\controller;

use   \think\Request;
use   \think\Db;
use   \think\Log;
use   \think\Session;
class Census extends \think\Controller
{

   //整份试卷的测评情况=>整体测评
    public function paper_tps(){
        //有很多变量没有做
        $data = Request::instance()->get();
        $res = Db::table('tps_evaluate_paper')->field('xueduan,xueke,fb_name,grade,fb_time,cp_time')->where('status = 2 and id ='.$data['id'].' and add_time = '.$data['souseid'])->find();
        $xueduan = Db::table('tps_paper_class')->where('id = '.$res['xueduan'])->value('name');
        $xueke = Db::table('tps_paper_class')->where('id = '.$res['xueke'])->value('name');
        $redata['xueke'] = $xueduan.$xueke;
        $redata['fb_time'] = $res['fb_time'];
        $redata['fb_name'] = $res['fb_name'];
        $redata['grade'] = $res['grade'];
        $redata['cp_time'] = $res['cp_time'];
        return view('paper_tps', [
            'redata'  => $redata,
        ]);

    } 

    //学员成绩详情
    public function xygkcj(){

        return view();

    } 

    //各题目详情
    public function gtmxq(){

        return view();

    } 

    //


   

}