<?php
/**
 * 商品模型
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-25
 * Time: 13:55
 */

namespace app\common\model;


use think\Model;

class Product extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    protected $autoWriteTimestamp = true;


}