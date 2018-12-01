<?php
namespace app\index\controller;

use think\Cache;
use think\Controller;
use think\Log;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch('404');
//        return $this->fetch();
    }

    /**
     * 测试方法
     */
    public function test()
    {
    }

    public function t()
    {
    }

}
