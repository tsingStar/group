<?php
/**
 * 网站目录管理
 * User: Administrator
 * Date: 2018/2/22
 * Time: 10:47
 */

namespace app\admin\controller;


use think\Request;

class Menu extends BaseController
{

    private $menuModel;

    public function __construct()
    {
        parent::__construct();
        $this->menuModel = new \app\admin\model\Menu();

    }

    /**
     * 菜单列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function menuList()
    {
        $menuList = $this->menuModel->select();
        $this->assign('totalNum', $this->menuModel->count());
        $this->assign('menuList', $menuList);
        return $this->fetch();
    }

    /**
     * 添加目录
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function menuAdd()
    {
        if (\request()->isAjax()) {
            addAdminOperaLog();
            $data = input('post.');
            $check = $this->validate($data, 'Menu');
            if (true !== $check) {
                exit_json(-1, $check);
            }
            $m = $this->menuModel->where(['name' => $data['name'], 'url' => $data['url']])->find();
            if ($m && $m['level'] == $data['level']) {
                exit_json(-1, '目录已存在');
            }
            $res = $this->menuModel->allowField(true)->save($data);
            if ($res) {
                exit_json(1, '保存成功');
            } else {
                exit_json(-1, '保存失败');
            }
        }
        return $this->fetch();
    }

    /**
     * 添加目录
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function menuEdit()
    {
        if (\request()->isAjax()) {
            addAdminOperaLog();
            $data = input('post.');
            $check = $this->validate($data, 'Menu');
            if (true !== $check) {
                exit_json(-1, $check);
            }
            $m = $this->menuModel->where(['name' => $data['name'], 'url' => $data['url'], 'parent_id'=>$data['parent_id']])->find();
            if ($m && $data['id'] == $m['id']) {
                exit_json(-1, '目录已存在');
            }
            $res = $this->menuModel->allowField(true)->save($data, ['id' => $data['id']]);
            if ($res) {
                exit_json(1, '保存成功');
            } else {
                exit_json(-1, '保存失败');
            }
        }
        $menu = $this->menuModel->where(['id' => input('menuId')])->find()->getData();
        $this->assign('menu', $menu);
        return $this->fetch();
    }

    /**
     * 更改节点状态
     */
    function changeStatus()
    {
        $menuId = input('post.id');
        $type = input('post.type');
        if($type == 1 || $type == 0){
            $res = $this->menuModel->changeStatus($menuId, $type);
            if($res){
                exit_json(1, '更改成功');
            }else{
                exit_json(-1, '更改失败');
            }
        }else{
            exit_json(-1, '参数错误');
        }


    }

    /**
     * 删除当前节点和下属节点
     */
    function menuDel()
    {
        $menuId = input('post.menuId');
        $res = $this->menuModel->delMenu($menuId);
        if ($res) {
            exit_json(1, '删除成功');
        } else {
            exit_json(-1, '删除失败');
        }
    }

    /**
     * 获取上级目录id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function getParentList()
    {
        $level = input('post.level');
        if ($level - 1 == 0) {
            $data = ['0' => '顶级目录'];
        } else {
            $res = $this->menuModel->field('id, name')->where(['level' => $level - 1])->select();
            $data = [];
            foreach ($res as $v) {
                $data[$v['id']] = $v['name'];
            }
        }
        exit_json(1, '获取上级目录成功', $data);
    }

}