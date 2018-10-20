<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/10/20
 * Time: 上午11:09
 */

namespace app\admin\controller;


class Header extends BaseController
{

    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 城主列表
     */
    public function index()
    {
        $params = input("get.");
        $list = model("Header")->order("create_time desc")->paginate(15, false, $params);
        $this->assign("list", $list);
        return $this->fetch();
    }



}