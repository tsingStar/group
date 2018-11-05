<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-21
 * Time: 16:23
 */

namespace app\wapp\controller;


use think\Controller;
use think\Log;

class Temp extends Controller
{

    protected function _initialize()
    {
        parent::_initialize();

    }

    public function testfn()
    {

        cache("test", ["1", "2"]);

        print_r(cache("test"));

        exit_json();

    }

    public function gfn()
    {
        for ($i=1; $i<20; $i++){
            Log::error($i);
            sleep(1);
        }

    }

    /**
     * 获取销售详情
     */
    public function getSaleRecord()
    {

        $list = model("HeaderGroup")->where("header_id", 1)->where("status", 2)->select();
        $data = [];
        $extraTitle = [];
        $header = [];
        foreach ($list as $item){
            //遍历每期结束军团
            $group_list = model("Order")->alias("a")->join("User b", "a.leader_id=b.id")->where("a.header_group_id", $item["id"])->group("a.leader_id")->order("sum(a.order_money-a.refund_money) desc")->field("b.residential, b.name, sum(a.order_money-a.refund_money) sale_money")->select();
            $temp = [];
            foreach ($group_list as $v){
                $temp[] = [$v["residential"], $v["name"], $v["sale_money"]];
            }
            $data[] = $temp;
            $extraTitle[] = substr($item['open_time'], 0, 10);
            $header[] = ["小区名称", "团长名称", "销售额"];
        }
        $file_name = "截止".date("Y-m-d")."军团销售记录";
        echo excel($header, $data, $file_name, count($data), $extraTitle);
        exit();
        
    }


}