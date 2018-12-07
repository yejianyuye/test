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
        return view('common/index');
    }
       
}
