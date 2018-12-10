<?php
namespace app\index\controller;

use think\Cache;
use think\Controller;
use think\Log;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch('404');
//        return $this->fetch();
    }

    public function downList()
    {
        return $this->fetch();
        
    }

    /**
     * 获取订单销售详情
     */
    public function getOrderSaleDetail()
    {
//        $start = "20181101000000";
//        $end = "20181130235959";
        $start = input("start_time");
        $end = input("end_time");
        $order_det_list = model("OrderDet")->alias("a")->join("HeaderGroupProduct b", "a.header_product_id=b.id")->where("a.order_no", "egt", $start)->where("a.order_no", "elt", $end)->where("header_id", 1)->field("a.order_no,a.product_name,b.purchase_price, a.market_price,a.group_price,a.num,a.back_num,b.commission")->select();
        $order_list = model("Order")->where("order_no", "egt", $start)->where("order_no", "elt", $end)->where("header_id", 1)->field("order_no, order_money, refund_money")->select();
        $data = [];
        foreach ($order_list as $value){
            $data[] = array_values($value->getData());
        }
        $data1 = [];
        foreach ($order_det_list as $item){
            $data1[] = array_values($item->getData());
        }
        $header = [
            ["订单编号", "订单金额", "退款金额"],
            ["订单编号", "商品名称", "商品进价", "市场价", "团购价", "数量","退货数量", "佣金比例（%）"],
        ];
        $data_list = [
            $data,
            $data1
        ];
        $extra_title = [
            "订单列表",
            "订单详情"
        ];
        echo excel($header, $data_list, md5(time()), 2, $extra_title);
        exit;

    }



    /**
     * 测试方法
     */
    public function test()
    {
        $list = model("HeaderGroup")->where("create_time", 'egt', strtotime("2018-10-01 00:00:00"))->where("create_time", 'elt', strtotime("2018-10-31   23:59:59"))->select();
        $sale_total = [];
        foreach ($list as $item){
            $sale = model("GroupProduct")->where("header_group_id", $item["id"])->field("sum(sell_num*group_price) total_money, sum(sell_num*group_price*commission/100) total_commission")->find()->getData();
            $sale["coupon"] = db("CouponRecord")->where("header_group_id", $item["id"])->where("status", 1)->sum("coupon");
            $sale["id"] = $item["id"];
            $sale["create_time"] = $item["create_time"];
            $sale["close_time"] = $item["close_time"];
            $sale_total[] = array_values($sale);
        }

        $header = [
            "销售总金额",
            "佣金总金额",
            "红包总费用",
            "团标识",
            "建团时间",
            "结团时间",
        ];
        echo excel($header, $sale_total, md5(time()), 1, []);
        exit();
        
    }

    public function t()
    {
        set_time_limit(0);
        $start_time = "2018-11-01 00:00:00";
        $end_time = "2018-11-05 23:59:59";
        $list = model("Order")->field("header_group_id, sum(order_money-refund_money) sum_money")->where("create_time", "egt", strtotime($start_time))->where("create_time", "elt", strtotime($end_time))->where("header_id", 1)->group("header_group_id")->select();
        $order_no_arr =  model("Order")->where("create_time", "egt", strtotime($start_time))->where("create_time", "elt", strtotime($end_time))->where("header_id", 1)->column("order_no");
        $data = [];
        foreach ($list as $val){
            $temp = [];
            $group = model("HeaderGroup")->where("id", $val["header_group_id"])->find();
            $temp[] = $group["id"];
            $temp[] = $group["create_time"];
            $temp[] = $group["close_time"];
            $temp[] = $val["sum_money"];
            //查询当前军团商品佣金比例
            $group_product = model("HeaderGroupProduct")->where("header_group_id", $val["header_group_id"])->column("commission", "id");
            Log::error($group_product);
            //计算当前团佣金
            $t = 0;
            foreach ($group_product as $product_id=>$commission){
                $t += model("OrderDet")->where("header_product_id", $product_id)->whereIn("order_no", $order_no_arr)->sum("(num-back_num)*group_price*$commission/100");
            }
            $temp[] = $t;
            //计算当前团红包费用
            $temp[] = db("CouponRecord")->where("header_group_id", $val["header_group_id"])->where("status", 1)->where("create_time", "egt", $start_time)->where("create_time", "elt", $end_time)->sum("coupon");
            $data[] = $temp;
        }
        $header = [
            "团标识",
            "建团时间",
            "结团时间",
            "销售总金额",
            "佣金总金额",
            "红包总费用",
        ];
        echo excel($header, $data, md5(time()), 1, []);
        exit();
    }

}
