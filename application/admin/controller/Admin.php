<?php
/**
 * 管理员
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/8
 * Time: 15:09
 */

namespace app\admin\controller;


use app\admin\model\Admins;

class Admin extends BaseController
{
    private $adminModel;
    private $roleModel;
    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new Admins();
        $this->roleModel = new \app\admin\model\Role();
        $roleList = $this->roleModel->column('role_name', 'id');
        $this->assign('roleList', $roleList);
    }

    /**
     * 管理员用户列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function adminList()
    {

        $map = [];
        if(input('get.mintime')){
            $map['create_time'][] = ['>', strtotime(input('get.mintime'))];
        }
        if(input('get.maxtime')){
            $map['create_time'][] = ['<', strtotime(input('get.maxtime'))];
        }
        if( isset($map['create_time']) && count($map['create_time'])==1){
            $map['create_time'] = $map['create_time'][0];
        }
        if(input('get.uname')){
            $map['uname'] = input('get.uname');
        }
        $adminList = $this->adminModel->getAdminList($map);
        $this->assign('list', $adminList);
        return $this->fetch();
    }

    /**
     * 管理员添加
     * @return mixed
     */
    function adminAdd()
    {
        if(request()->isAjax()){
            $data = input('post.');
            $data['role_id'] = join(',', $data['role_id']);
            $data['password'] = md5($data['password']);
            $check = $this->validate($data, 'Admin');
            if(true !== $check){
                exit_json(-1, $check);
            }
            $res = $this->adminModel->allowField(true)->isUpdate(false)->save($data);
            if($res){
                exit_json(1, '保存成功');
            }else{
                exit_json(-1, '保存失败');
            }
        }else{
            return $this->fetch();
        }
    }

    /**
     * 管理员编辑
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function adminEdit()
    {
        if(request()->isAjax()){
            $data = input('post.');
            $data['role_id'] = join(',', $data['role_id']);
            $res = $this->adminModel->allowField(['role_id', 'uname', 'describe', 'name'])->save($data, ['id'=>$data['id']]);
            if($res){
                exit_json(1, '保存成功');
            }else{
                exit_json(-1, '保存失败');
            }
        }else{
            $admin = $this->adminModel->where('id', 'eq', input('adminId'))->find();
            $roleArr = explode(',', $admin['role_id']);
            $this->assign('admin', $admin);
            $this->assign('roleArr', $roleArr);
            return $this->fetch();
        }
    }

    /**
     * 检验名字是否存在
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function checkName()
    {
        $name = input('uname');
        $adminId = input('id');
        $map['uname'] = $name;
        if($adminId){
            $map['id']=['neq', $adminId];
        }
        $admin = $this->adminModel->where($map)->find();
        if($admin){
            echo "false";
        }else{
            echo "true";
        }
    }

    function del(){
        $adminId = input('id');
        $res = $this->adminModel->where('id', 'eq', $adminId)->delete();
        if($res){
            exit_json();
        }else{
            exit_json(-1, '删除失败');
        }
    }

    /**
     * 更改管理员状态
     */
    public function changeStatus()
    {
        $id = input('id');
        $enable = input('enable');
        $res = model('admins')->save(['enable'=>$enable], ['id'=>$id]);
        if($res){
            exit_json(1, '更新成功');
        }else{
            exit_json(1, '更新失败');
        }
    }


}