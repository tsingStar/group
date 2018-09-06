<?php
/**
 * 目录实体类
 * User: Administrator
 * Date: 2018/2/9
 * Time: 15:53
 */

namespace app\admin\model;


use think\Model;

class Menu extends Model
{
    protected $autoWriteTimestamp = 'true';

    public function initialize()
    {
        parent::initialize();

    }

    public function getDisplayAttr($value)
    {
        $arr = ['1' => '启用', '0' => '已停用'];
        return $arr[$value];
    }

    public function getLevelAttr($value)
    {
        $arr = ['1' => '一级目录', '2' => '二级目录', '3' => '三级目录'];
        return $arr[$value];
    }

    /**
     * 修改节点状态
     * @param $nodeId
     * @param $type
     * @return false|int
     */
    function changeStatus($nodeId, $type){
        return $this->save(['display'=>$type], ['id'=>$nodeId]);
    }

    /**
     * 删除节点
     * @param $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function delMenu($id)
    {
        $nodesId = $this->getAllNodes($id);
        $nodesId[] = $id;
        $res = $this->where('id', 'in', $nodesId)->delete();
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取指定节点下的所有节点id
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function getAllNodes($id)
    {
        $nodeIds = [];
        $res = $this->where(['parent_id' => $id])->select();
        foreach ($res as $r) {
            $nodeIds[] = $r['id'];
            $tempNode = $this->getAllNodes($r['id']);
            $nodeIds = array_merge($nodeIds, $tempNode);
        }
        return $nodeIds;
    }


    /**
     * 获取导航目录
     * @param $nodeList
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function getNavList($nodeList)
    {
        $map = [];
        if ($nodeList !== 'all') {
            $map['id'] = ['in', $nodeList];
        }
        $map['display'] = 1;
        $list = $this->where($map)->select();
        $menuList = getTree($list, 0);
        return $menuList;
    }

    /**
     * 获取面包屑
     * @param $url
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function getNavBread($url)
    {
        $one = $this->where(['url' => $url])->find();
        $two = $this->where(['id' => $one['parent_id']])->find();
        $three = $this->where(['id' => $two['parent_id']])->find();
        return '<i class="Hui-iconfont">&#xe67f;</i> ' . $three['name'] . ' <span class="c-gray en">&gt;</span> ' . $two["name"] . ' <span class="c-gray en">&gt;</span> ' . $one["name"];
    }

}