<?php
/**
 * Created by PhpStorm.
 * User: TangHanqing
 * Date: 2017/12/4
 * Time: 14:54
 */
namespace app\index\event;

class Blog
{
    public function insert()
    {
        return 'insert';
    }

    public function update($id)
    {
        return 'update:'.$id;
    }

    public function delete($id)
    {
        return 'delete:'.$id;
    }
}