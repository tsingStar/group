<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-17
 * Time: 10:14
 */

namespace app\common\model;


use think\Cache;
use think\Model;

class User extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    protected $autoWriteTimestamp = true;

    /**
     * 获取用户基本信息
     * @param int $user_id
     * @param null $open_id
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo($user_id=0, $open_id=null)
    {
        if(!is_null($open_id)){
            if(Cache::has($open_id.":user")){
                return Cache::get($open_id.":user");
            }else{
                $user = $this->field("id, user_name, avatar, open_id, header_id, role_status")->where("open_id", $open_id)->find();
                if($user){
                    Cache::set($open_id.":user", $user->getData());
                    return $user->getData();
                }else{
                    $this->error = "用户不存在";
                    return false;
                }
            }
        }

        if($user_id){
            if(Cache::has($user_id.":user")){
                return Cache::get($user_id.":user");
            }else{
                $user = $this->field("id, user_name, avatar, open_id, header_id, role_status")->where("id", $user_id)->find();
                if($user){
                    Cache::set($user_id.":user", $user->getData());
                    return $user->getData();
                }else{
                    $this->error = "用户不存在";
                    return false;
                }
            }
        }

        $this->error = "参数异常";
        return false;
    }

}