<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-03
 * Time: 09:24
 */

namespace app\header\controller;


use app\common\model\HeaderGroup;

class Group extends ShopBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 团购列表
     */
    public function index()
    {

        $list = HeaderGroup::where(['header_id'=>HEADER_ID])->order('create_time')->paginate(10);
        $this->assign('list', $list);
        return $this->fetch();
    }


}