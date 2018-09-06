<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 8:52
 */

namespace app\admin\controller;


class Role extends BaseController
{
    private $roleModel;

    public function __construct()
    {
        parent::__construct();
        $this->roleModel = new \app\admin\model\Role();
    }

    /**
     * 角色列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function roleList()
    {
        $roleList = $this->roleModel->select();
        $this->assign('roleList', $roleList);
        return $this->fetch();
    }

    /**
     * 添加角色
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function roleAdd()
    {
        if (request()->isAjax()) {
            addAdminOperaLog();
            $data = input('post.');
            $node_id = join(',', array_unique($data['node_id']));
            $data['node_id'] = join(',', array_unique(explode(',', $node_id)));
            $check = $this->validate($data, 'Role');
            if ($check !== true) {
                exit_json(-1, $check);
            }
            $isExist = $this->roleModel->where(['role_name' => $data['role_name']])->find();
            if ($isExist) {
                exit_json(-1, '角色名称已存在');
            } else {
                $res = $this->roleModel->allowField(['role_name', 'describe', 'node_id'])->save($data);
                if ($res) {
                    exit_json(1, '角色添加成功');
                } else {
                    exit_json(-1, '添加角色失败');
                }
            }
        } else {
            $menuModel = new \app\admin\model\Menu();
            $menuList = $menuModel->getNavList('all');
            $this->assign('menuList', $menuList);
            return $this->fetch();
        }
    }

    /**
     * 编辑角色
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function roleEdit()
    {
        if (request()->isAjax()) {
            addAdminOperaLog();
            $data = input('post.');
            $node_id = join(',', array_unique($data['node_id']));
            $data['node_id'] = join(',', array_unique(explode(',', $node_id)));
            $check = $this->validate($data, 'Role');
            if ($check !== true) {
                exit_json(-1, $check);
            }
            $isExist = $this->roleModel->where(['role_name' => $data['role_name'], 'id'=>['neq', $data['id']]])->find();
            if ($isExist) {
                exit_json(-1, '角色名称已存在');
            } else {
                $res = $this->roleModel->allowField(['role_name', 'describe', 'node_id'])->save($data, ['id'=>$data['id']]);
                if ($res) {
                    exit_json(1, '角色保存成功');
                } else {
                    exit_json(-1, '保存角色失败');
                }
            }
        } else {
            $roleId = input('roleId');
            $role = $this->roleModel->where('id', 'eq', $roleId)->find();
            $menuModel = new \app\admin\model\Menu();
            $menuList = $menuModel->getNavList('all');
            $this->assign('menuList', $menuList);
            $this->assign('accessNode', explode(',', $role['node_id']));
            $this->assign('role', $role);
            return $this->fetch();
        }
    }

    /**
     * 校验名称是否存在
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function checkRoleName()
    {
        $roleId = input('roleId');
        $roleName = input('role_name');
        $map = [];
        if($roleId){
            $map['id'] = ['neq', $roleId];
        }
        if($roleName){
            $map['role_name'] = $roleName;
        }
        $r = $this->roleModel->where($map)->find();
        if($r){
            exit("false");
        }else{
            exit("true");
        }
    }


    /**
     * 删除角色
     */
    function roleDel()
    {
        $roleId = input('post.roleId');
        if($roleId == 1){
            exit_json(-1, '当前角色禁止删除');
        }
        if (!$roleId) {
            exit_json(-1, '参数错误');
        }
        $res = $this->roleModel->where(['id' => $roleId])->delete();
        if ($res) {
            addAdminOperaLog();
            exit_json(1, '删除成功');
        } else {
            exit_json(-1, '删除失败');
        }
    }


}