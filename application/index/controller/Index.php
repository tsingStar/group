<?php
namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch('404');
    }

    public function report()
    {
        $r_id = input('r_id');
        $rep = model('report')->alias('a')->join('shop b', 'a.shop_id=b.id')->field('a.*, b.shopname')->where('a.id', $r_id)->find();
        $this->assign('rep', $rep);
        return $this->fetch();
    }

    public function swiper()
    {
        $id = input('s_id');
        $swiper = model('swiper')->alias('a')->join('shop b', 'a.shop_id=b.id')->field('a.*, b.shopname')->where('a.id', $id)->find();
        $this->assign('rep', $swiper);
        return $this->fetch('report');
    }

    public function join_us()
    {
        if(request()->isPost()){
            if(session('token') == input('token')){
                $name = input('name');
                $telephone = input('telephone');
                $area = input('area');
                $email = input('email');
                $res = model('join_us')->save(['name'=>$name, 'telephone'=>$telephone, 'area'=>$area, 'email'=>$email]);
                if($res){
                    session('token', null);
                    echo '<script>alert("提交成功")</script>';
                    header("Refresh:0");
                }else{
                    echo '<script>alert("提交失败")</script>';
                }
            }else{
                echo "<script> alert('重复提交')</script>";
            }
        }
        $token = md5(time());
        session('token', $token);
        $this->assign('token', $token);
        return $this->fetch();
    }

    /**
     * 用户注册协议
     */
    public function register()
    {
        $res = db('web_register')->where('id', 1)->find();
        $this->assign('item', $res);
        return $this->fetch();
    }
    /**
     * 关于我们
     */
    public function about_us()
    {
        $res = db('web_about_us')->where('id', 1)->find();
        $this->assign('item', $res);
        return $this->fetch('register');
    }

}
