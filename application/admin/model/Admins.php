<?php
/**
 * 管理员实体类
 * User: tsingStar
 * Date: 2018/1/10
 * Time: 9:27
 */

namespace app\admin\model;


use think\Model;

class Admins extends Model
{

    protected $autoWriteTimestamp = true;
    function getCreateTimeAttr($value)
    {
        return date("Y-m-d H:i:s", $value);
    }

    /**
     * @param array $map
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function getAdminList($map=[])
    {
        return $this->where($map)->select();
    }
    /**
     * @param $name
     * @param $password
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function checkAdmin($name, $password){
        $admin = $this->where(['uname'=>$name, 'password'=>md5($password)])->find();
        if($admin){
            $this->save(['login_ip'=>ip2long(request()->ip()), 'login_time'=>time()], ['id'=>$admin['id']]);
            return $admin;
        }else{
            return false;
        }
    }


}