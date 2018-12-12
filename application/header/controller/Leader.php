<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-18
 * Time: 10:41
 */

namespace app\header\controller;



use think\Cache;

class Leader extends ShopBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $list = model("User")->where("role_status", 2)->where("header_id", HEADER_ID)->paginate(10);
        $this->assign("totalNum", model("User")->where("role_status", 2)->where("header_id", HEADER_ID)->count());
        $this->assign("list", $list);
        return $this->fetch();
        
    }

    /**
     * 申请团长记录列表
     */
    public function applyList()
    {

        $list = model('ApplyLeaderRecord')->where('header_id', HEADER_ID)->paginate(10);
        $this->assign("totalNum", model("ApplyLeaderRecord")->where("header_id", HEADER_ID)->count());
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 待处理申请记录
     */
    public function readyList()
    {
        $list = model("ApplyLeaderRecord")->where("header_id", HEADER_ID)->where("status", 0)->paginate(10);
        $this->assign("list", $list);
        $this->assign("totalNum", model("ApplyLeaderRecord")->where("status", 0)->where("header_id", HEADER_ID)->count());
        return $this->fetch("applyList");
    }

    /**
     * 团长提现记录
     */
    public function withdrawList()
    {
        $leader_ids = model("User")->where("header_id", HEADER_ID)->column("id");
        $list = model("WithdrawLog")->alias("a")->join("User b", "a.user_id=b.id")->where("a.role", 2)->whereIn("a.user_id", $leader_ids)->field("a.*, b.name")->where("a.status", 1)->order("a.create_time desc")->paginate(10);
        $this->assign("list", $list);
        $this->assign("totalNum", model("WithdrawLog")->alias("a")->join("User b", "a.user_id=b.id")->where("a.role", 2)->whereIn("a.user_id", $leader_ids)->field("a.*, b.name")->where("a.status", 1)->count());
        return $this->fetch();
    }

    /**
     * 团长申请明细
     */
    public function detail()
    {
        $id = input("id");
        $item = model("ApplyLeaderRecord")->where("id", $id)->find();
        $this->assign("item", $item);
        return $this->fetch();
    }

    /**
     * 同意团长
     */
    public function agree()
    {
        $apply_id = input('id');
        $data = model("ApplyLeaderRecord")->where("id", $apply_id)->find();
        if ($data && $data['status'] == 0) {
            if ($data['header_id'] != HEADER_ID) {
                exit_json(-1, '权限错误');
            }
            $data->save(['status' => 1]);
            $user = model('User')->where('id', $data['user_id'])->find();
            $user->save(['role_status' => 2, "header_id" => HEADER_ID, "telephone" => $data['telephone'], "address" => $data["address"], "residential" => $data["residential"], "neighbours" => $data["neighbours"], "have_group" => $data["have_group"], "have_sale" => $data["have_sale"], "work_time" => $data["work_time"], "name" => $data["name"]]);
            Cache::rm($user["id"].":user");
            Cache::rm($user["open_id"].":user");
            exit_json();
        } else {
            exit_json(-1, '申请记录不存在或已处理');
        }
        
    }

    /**
     * 拒绝
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refuse()
    {
        $apply_id = input('id');
        $data = model("ApplyLeaderRecord")->where("id", $apply_id)->find();
        $reason = input('reason');
        if ($data && $data['status'] == 0) {
            if ($data['header_id'] != HEADER_ID) {
                exit_json(-1, '权限错误');
            }
            $data->save(['status' => 2, 'remarks' => $reason]);
            exit_json();
        } else {
            exit_json(-1, '申请记录不存在或已处理');
        }
    }

    /**
     * 取消团长身份
     */
    public function cancel()
    {
        $id = input("id");
        $leader = model("User")->where("id", $id)->where("header_id", HEADER_ID)->find();
        if(!$leader){
            exit_json(-1, "团长不存在");
        }else{
            $leader->save(["role_status"=>1]);
            exit_json();
        }
    }





}