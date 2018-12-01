<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/11/30
 * Time: 15:25
 */

namespace app\index\model;


use think\Model;

class Test extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }
    protected $autoWriteTimestamp = false;

}