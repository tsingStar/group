<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/22
 * Time: 10:49
 */

namespace app\admin\controller;


use app\admin\model\Admins;
use think\Controller;

class BaseController extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->assign('sitename', config('sitename'));
        $this->assign('breadNav', $this->getNavBread());
        $this->checkIsLogin();
    }

    //验证是否登陆
    function checkIsLogin()
    {
        if (session('admin')) {
            return true;
        } else {
            if (cookie('admin_id') && cookie('admin_pwd')) {
                $adminModel = new Admins();
                $admin = $adminModel->where(['id' => cookie('admin_id'), 'password' => cookie('admin_pwd')])->find();
                if ($admin) {
                    set_admin_login($admin);
                    return true;
                }
            }
            $this->redirect('admin/Pub/login');
        }
    }
    //生成面包屑导航
    function getNavBread()
    {
        $module = request()->module();
        $controller = request()->controller();
        $action = request()->action();
        $url = $module.'/'.$controller.'/'.$action;
        $menuModel = new \app\admin\model\Menu();
        $navBread = $menuModel->getNavBread($url);
        return $navBread;
    }
    //访问权限判断
    function access(){



    }

}