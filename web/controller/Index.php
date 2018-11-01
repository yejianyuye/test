<?php
namespace app\web\controller;
use think\Controller;
use think\View;
use think\Db;
use think\Model;

//use think\Validate;
//namespace think;
//require_once   $_SERVER['DOCUMENT_ROOT'].'/thinkphp/library/think/App.php';
//include   '192.168.68.49:8080/thinkphp/library/think/App.php';
//http://192.168.68.49:8080
class Index extends Model
{
    public function index()
    {
        return $this->fetch('index');
    }

    public function login()
    {

       // return $this->fetch('index/index');
        return view('test');
    }


    public function test()
    {
       /* $request = Request::instance();
    // 获取当前域名
        echo '获取当前域名' . $request->domain() . '<br/>';
    // 获取当前入口文件
        echo '获取当前入口文件' . $request->baseFile() . '<br/>';
    // 获取当前URL地址 不含域名
        echo '获取当前URL地址 不含域名' . $request->url() . '<br/>';
    // 获取包含域名的完整URL地址
        echo '获取包含域名的完整URL地址' . $request->url(true) . '<br/>';
    // 获取当前URL地址 不含QUERY_STRING
        echo '获取当前URL地址 不含QUERY_STRING' . $request->baseUrl() . '<br/>';
    // 获取URL访问的ROOT地址
        echo '获取URL访问的ROOT地址' . $request->root() . '<br/>';
    // 获取URL访问的ROOT地址
        echo 'root with domain: ' . $request->root(true) . '<br/>';
    // 获取URL地址中的PATH_INFO信息
        echo 'pathinfo: ' . $request->pathinfo() . '<br/>';
    // 获取URL地址中的PATH_INFO信息 不含后缀
        echo 'pathinfo: ' . $request->path() . '<br/>';
    // 获取URL地址中的后缀信息
        echo 'ext: ' . $request->ext() . '<br/>';
        echo "当前模块名称是" . $request->module(). '<br/>';
        echo "当前控制器名称是" . $request->controller(). '<br/>';
        echo "当前操作名称是" . $request->action(). '<br/>';*/
       // var_dump($this->request->url(true));
       // die;
        //$this->_initialize();
       // $this->assign('domain',$this->request->url(true));
       // return $this->fetch('index');
        //return view();
        //$user = Db::table('fa_admin')->where(array('nickname'=>'Admin','avatar'=>1))->column('id,username');
       // $query = new \think\db\Query();
       // $query->table('fa_admin')->where('status',1);

      //  Db::find($query);
        //Db::select($query);
        //$data = ['nickname' => 'Admin', 'avatar' => '2'];
        //$rr = Db::name('fa_admin')->insertGetId($data);
       // Db::table('fa_admin')
       //     ->where('id', 1)
       //     ->update([
        //        'logintime'  => ['exp','now()'],
      //          'createtime' => ['exp','createtime+1'],
     //       ]);
       // Db::table('fa_admin')->where('id',1)->setField('username', 'wodecuo');

       /* $user = Db::table('fa_admin')
            ->where('username','like','%admin%')
            ->select();
        Db::table('think_user')
            ->where('name&title','like','%thinkphp')
            ->find();
        Db::table('think_user')
            ->where('name|title','like','%thinkphp')
            ->find();*/
        //$user = Db::query('call test(1)');

        //var_dump($rr);die;

        $user           = new Admin;
        $user->username     = 'thinkphp';
        $user->nickrname    = 'thinkphp@qq.com';
        //$user->save();
        mysql_get_server_info();die;
       $user = Db::table('fa_admin')->where('id','1')->find();
        var_dump($user);die;
    }


    public function _empty($name)
    {
        //把所有城市的操作解析到city方法
        return $this->showCity($name);
    }

    //注意 showCity方法 本身是 protected 方法
    protected function showCity($name)
    {
        //和$name这个城市相关的处理
        return '当前城市' . $name;
    }

    public function edit($id,$ss)
    {
        echo $ss;
       var_dump($id);die;
    }
}
