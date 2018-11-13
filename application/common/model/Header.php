<?php
/**
 * 城主
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-28
 * Time: 10:11
 */

namespace app\common\model;


use think\Cache;
use think\Model;

class Header extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    protected $autoWriteTimestamp = true;

    /**
     * 获取城主地址
     * @param int $header_id 城主id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHeaderAddress($header_id)
    {
        if(!Cache::has($header_id.":address")){
            $address = $this->field("id, nick_name, address, telephone, header_image")->where("id", $header_id)->find();
            Cache::set($header_id.":address", $address->getData());
        }
        return Cache::get($header_id.":address");
    }




}