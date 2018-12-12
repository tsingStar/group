<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/19
 * Time: 15:34
 */

namespace app\header\controller;


use think\Controller;

class ShopBase extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
        if(request()->controller() != 'Index'){
            $this->assign('breadNav', $this->getBreadNav());
        }
        if(!self::checkIsLogin()){
            if(request()->isAjax()){
                exit_json(-1, "用户登陆已过期");
            }else{
                $this->redirect('Pub/login');
            }
        }



    }

    /**
     * 检验城主的合法性
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkIsLogin()
    {
        if (session(config('headerKey'))) {
            if (!defined('HEADER_ID')) {
                define('HEADER_ID', session(config('headerKey')));
            }
            return true;
        } else {
            if(cookie('header_id') && cookie('header_pwd')){
                $shop = model('Header')->where([
                    'id'=>cookie('header_id'),
                    'password'=>cookie('header_pwd')
                ])->find();
                if($shop){
                    session(config('headerKey'), $shop['id']);
                    if (!defined('Header_ID')) {
                        define('HEADER_ID', session(config('headerKey')));
                    }
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * 获取面包屑导航
     */
    private function getBreadNav()
    {
        require_once APP_PATH . 'header/leftmenu.php';
        $menuArr = $leftmenu;
        $action = request()->controller();
        $method = request()->action();
        $bread = "";
        foreach ($menuArr as $m) {
            foreach ($m['navChild'] as $t){
                if($t['url'] == $action.'/'.$method){
                    $bread = '<i class="Hui-iconfont">&#xe67f;</i> ' . $m['navName'] . ' <span class="c-gray en">&gt;</span> ' . $t["navName"] ;
                    break;
                }
            }
        }
        return $bread;
    }



}