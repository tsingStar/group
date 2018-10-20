<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/10/18
 * Time: 上午8:56
 */

namespace app\common\model;


use think\Model;

class GroupRecord extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    protected $autoWriteTimestamp = true;

}