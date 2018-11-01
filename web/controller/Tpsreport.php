<?php
namespace app\web\controller;

use   \think\Request;
use   \think\Db;

class Tpsreport extends \think\Controller
{

    //测评总报告
    public function report_all(){
        $data = Request::instance()->get();
        $zhunkaonum = $data['zhunkaonum'];
        $evaluate = $data['evaluate'];
        $geke = $this->get_paperkemu($evaluate);
       // var_dump($geke);die;
        $student_info = Db::table('tps_wanrence_student')
            ->alias('s')
            ->field('s.student_name,s.student_num,s.zhunkaonum,s.nianji,s.kemu,s.fenshu,a.kemu_id,x.xiaoqu_name,w.kaoshikemu,w.pingjunfen')
            ->join('tps_kemu_paper a','a.evaluate_paper_id = s.evaluate_paper_id')
            ->join('tps_all_pjf w','w.evaluate_paper_id ='.$evaluate.' and s.xiaoqu_id = w.xiaoqu_id')
            ->join('tps_all_xiaoqu x','x.id = s.xiaoqu_id')
            ->where('s.zhunkaonum ='.$zhunkaonum.' and s.evaluate_paper_id ='.$evaluate)
            ->find();
        $xdf_fs = Db::table('tps_all_pjf')->field('kaoshikemu,pingjunfen')->where('evaluate_paper_id ='.$evaluate.' and xiaoqu_id = 0')->find();
        $gr_k = explode(',',$student_info['kemu']);
        $gr_f = explode(',',$student_info['fenshu']);
        $xq_k = explode(',',$student_info['kaoshikemu']);
        $xq_f = explode(',',$student_info['pingjunfen']);
        $xdf_k = explode(',',$xdf_fs['kaoshikemu']);
        $xdf_f = explode(',',$xdf_fs['pingjunfen']);
        $arr = array();
        $ks_name = '';
        foreach($xdf_k as $xk=>$xv){
            $ss = Db::table('tps_dk_kemuname')->field('kemuname')->where('id='.$xdf_k[$xk])->find();
            //新东方平均分
            $arr[$xk]['xdf_km'] = $ss['kemuname'];
            if($xdf_k[$xk] != 0){
                $ks_name .= $ss['kemuname'].'、';
            }
            $arr[$xk]['xdf_fs'] = $xdf_f[$xk];
            //个人分数
            foreach($gr_k as $gk=>$gv){
                if($xv == $gv){
                    $arr[$xk]['gr_fs'] = $gr_f[$gk];
                }
            }
            foreach($xq_k as $qk=>$qv){
                if($xv == $qv){
                    $arr[$xk]['xq_fs'] = $xq_f[$qk];
                }
            }
        }
        $student_info['ks_name'] = rtrim($ks_name, "、");
        return view('report_all', [
            'student_info'  => $student_info,
            'arr'  => $arr,
            'xdf_f'  => $xdf_f,
            'data'  => $data,
            'geke'  => $geke,
        ]);
    }


    //各科测评
    public function report_kemu(){
        $data = Request::instance()->get();
        //考试id
        $papername_id = $data['evaluate'];
        $zhunkaonum = $data['zhunkaonum'];
        //试卷
        $xueke = $data['xueke'];

        $geke = $this->get_paperkemu($papername_id);
        //学生基本知识掌握情况......  根据学科id
        $paper_info= Db::table('tps_dk_kemuname')
            ->alias('tdk')
            ->field('tdk.kemuname,tdz.proportion,tdz.all_num,tdz.acg,tpd.kaodian_name,tdz.kaodian_id,tdz.id as tsdid')
            ->join('tps_paper_kaodian tpd','tpd.xueke_id = tdk.id')
            ->join('tps_dk_zsd tdz','tdz.kaodian_id = tpd.kaodian_id and tdz.xueke_id='.$xueke.' and tdz.papername_id='.$papername_id.'')
            ->where('tdk.id='.$xueke)
            ->order('tpd.kaodian_id asc')
            ->select();
        //var_dump($paper_info);die;
        $student_paper = Db::table('tps_dk_rstudent')->where('zhunkaonum ='.$zhunkaonum.' and papername_id ='.$papername_id.' and xueke_id ='.$xueke)->find();
        if($student_paper == null){
           echo '该学科学生成绩未录入';die;
        }
        $student_kd = explode(',',$student_paper['kd_var']);
        $student_acg = explode(',',$student_paper['acg_var']);
        $student_zwd = explode(',',$student_paper['zwd_var']);

        //以考点为基准进行校准
        $error_answer = array();
        foreach($paper_info as $pk=>$pv){
            $paper_info[$pk]['kaodian_nid'] = $paper_info[$pk]['kaodian_id'].'.'.$paper_info[$pk]['kaodian_name'];
            $paper_info[$pk]['acg'] = $paper_info[$pk]['acg']*100;
            //个人知识点正确率
            foreach($student_kd as $gk=>$gv){
                if($pv['kaodian_id'] == $gv){
                    $paper_info[$pk]['gr_acg'] = $student_acg[$gk]*100;
                    $paper_info[$pk]['gr_acgs'] = $student_acg[$gk];
                    $paper_info[$pk]['gr_zwd'] = $student_zwd[$gk];
                }
            }
            //根据考试papername_id以及xueke_id获取考生以及新东方的知识点下面考点的真确性
            $ss= Db::table('tps_dk_tstudent')
                ->alias('tdt')
                ->field('tdt.acg as gracg,tdt.zwd,tdt.isok,tds.acg as xdfacg,tds.zsd_sm,tds.num')
                //->field('s.student_name,s.student_num,s.zhunkaonum,s.nianji,s.kemu,s.fenshu,a.kemu_id,x.xiaoqu_name,w.kaoshikemu,w.pingjunfen')
                ->join('tps_dk_th tds','tdt.num = tds.num and tds.zsd_id='.$pv['tsdid'].' and tds.xueke_id='.$xueke.' and tds.papername_id='.$papername_id)

                ->where('tdt.zsd_id='.$pv['tsdid'].' and tdt.zhunkaonum ='.$zhunkaonum.' and tdt.xueke_id ='.$xueke)
                ->select();
           // var_dump($ss);die;
            foreach($ss as $sl=>$sv){
                $ss[$sl]['gracg'] = $ss[$sl]['gracg']*100;
                $ss[$sl]['xdfacg'] = $ss[$sl]['xdfacg']*100;
                //知识点错误情况
                if($ss[$sl]['isok'] == 0){
                    $error_answer[$pk]['kaodian_name'] = $paper_info[$pk]['kaodian_name'];
                    $error_answer[$pk]['error'][$sl] = $ss[$sl]['zsd_sm'];
                }

            }
            $paper_info[$pk]['xdf_th'] = $ss;
        }

     //   var_dump($paper_info);die;
        //错误知识点情况以及个人诊断报告明细
        //以试卷知识点为节点——》找到知识点下面的考点-》从而得到报告明细
        return view('report_kemu', [
            'paper_info'  => $paper_info,
            'student_paper'  => $student_paper,
            'error_answer'  => $error_answer,
            'data'  => $data,
            'geke'  => $geke,
        ]);

    }

    //获取考试科目
    public function get_paperkemu($evaluate){

        $ss = Db::table('tps_kemu_paper')->where('evaluate_paper_id='.$evaluate)->value('kemu_id');
        //$sss = Db::table('tps_dk_kemuname') ->where('id','in',$ss)->select();
        $sss = Db::table('tps_dk_kemuname') ->where('id in ('.$ss.')')->select();
        return $sss;


    }

    //获取名称
    public function get_titlename(){

        $data = Request::instance()->get();
        $titel_info['title'] = Db::table('tps_zh_testname') ->where('id ='.$data['evaluate'])->value('testname');
        $titel_info['name'] = Db::table('tps_dk_rstudent') ->where('zhunkaonum ='.$data['zhunkaonum'])->value('student_name');

        return view('report_title', [
            'titel_info'  => $titel_info,
        ]);

    }



}