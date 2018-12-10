<?php
/**
 * 支付结果通知
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018.6.4
 * Time: 09:31
 */

namespace app\admin\controller;


use app\common\model\WeiXinPay;
use think\Controller;
use think\Exception;
use think\Log;

class PayResult extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 支付通知入口
     */
    public function index()
    {
        set_time_limit(0);
        //微信支付来源
        $xml = file_get_contents('php://input');
        $_POST = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $log_path = LOG_PATH . 'weixin';
        is_dir($log_path) or mkdir($log_path, "0777");
        file_put_contents($log_path . DS . date("Y-m-d") . ".txt", date("H:m:s") . json_encode($_POST) . "\r\n", FILE_APPEND);
        if ($_POST['result_code'] == 'SUCCESS') {
            $weixin = new WeiXinPay();
            $validRes = $weixin->chargeNotify();
            if ($validRes === false) {
                Log::error('签名错误');
            }
            $orderInfo = $this->formatRes($validRes);
            //测试
//            $orderInfo=[
//                "out_trade_no"=>"201811301106176268802",
//                "total_money"=>35,
//                "trade_no"=>"353423523423423323"
//            ];
            //测试
            try{
//                $f = fopen(__PUBLIC__."/pay.txt", "w");
//                if(flock($f, LOCK_EX)){
                    model('OrderPre')->startTrans();
                    model('Order')->startTrans();
                    model('OrderDet')->startTrans();
                    $order_pre = model('OrderPre')->where('order_no', $orderInfo['out_trade_no'])->find();
                    if ($order_pre['status'] === 0) {
                        $order_det = json_decode($order_pre['order_det'], true);
                        if ($orderInfo['total_money'] == $order_det['order_money']) {
                            //订单支付成功
                            //修改预支付订单状态，填写用户订单及订单详情
                        $res1 = $order_pre->save(['status' => 1, 'transaction_id' => $orderInfo['trade_no']]);
//                            $res1 = true;
                            $order_det["transaction_id"] = $orderInfo['trade_no'];

                            //添加订单序号
                            $num = model("Order")->getLastNum($order_det["group_id"]);
                            $order_det["num"] = $num;
                            //添加订单序号结束
                            $res2 = model('Order')->allowField(true)->save($order_det);
                            $product_list = [];
                            foreach ($order_det['product_list'] as $item) {
                                //TODO 待处理 团购商品
                                $gp = model("GroupProduct")->where("leader_id", $order_det["leader_id"])->where("header_group_id", $order_det["header_group_id"])->where("group_id", $order_det["group_id"])->where("header_product_id", $item["id"])->find();
                                if(!$gp){
                                    $p_data = [
                                        "leader_id"=>$order_det["leader_id"],
                                        "header_group_id"=>$order_det["header_group_id"],
                                        "group_id"=>$order_det["group_id"],
                                        "header_product_id"=>$item["id"],
                                        "product_name"=>$item["product_name"],
                                        "tag_name"=>$item["tag_name"],
                                        "sell_num"=>0,
                                        "refund_num"=>0,
                                        "commission"=>$item["commission"],
                                        "market_price"=>$item["market_price"],
                                        "group_price"=>$item["group_price"],
                                        "group_limit"=>$item["group_limit"],
                                        "self_limit"=>$item["self_limit"],
                                        "product_desc"=>$item["product_desc"],
                                        "ord"=>0
                                    ];
                                    $p_id = model("GroupProduct")->insertGetId($p_data);
                                }else{
                                    $p_id = $gp["id"];
                                }
                                $product_list[] = [
                                    "leader_id"=>$order_det["leader_id"],
                                    "user_id"=>$order_det["user_id"],
                                    "header_group_id"=>$order_det["header_group_id"],
                                    "group_id"=>$order_det["group_id"],
                                    "product_id"=>$p_id,
                                    "header_product_id"=>$item["id"],
                                    "order_no"=>$orderInfo["out_trade_no"],
                                    "product_name"=>$item["product_name"],
                                    "num"=>$item["num"],
                                    "market_price"=>$item["market_price"],
                                    "group_price"=>$item["group_price"],
                                ];
                            }
                            $res3 = model('OrderDet')->allowField(true)->saveAll($product_list);
                            if ($res1 && $res2 && $res3) {
                                //订单支付后处理商品库存及销量信息
                                model("Order")->orderSolve($product_list);


                                model('OrderPre')->commit();
                                model('Order')->commit();
                                model('OrderDet')->commit();

                                //订单商品库存处理
                                model("OrderRemainPre")->where("order_no", $orderInfo["out_trade_no"])->setField("status", 1);
                                //处理团购访问记录
                                $grc = model("GroupRecord")->where("group_id", $order_det["group_id"])->where("user_id", $order_det["user_id"])->find();
                                if($grc){
                                    $grc->save(["status"=>1]);
                                }


                            } else {
                                model('OrderPre')->rollback();
                                model('Order')->rollback();
                                model('OrderDet')->rollback();
                                Log::error("订单处理异常" . $orderInfo['out_trade_no']);
                            }
                        } else {
                            Log::error("订单支付金额错误" . $orderInfo['out_trade_no']);
                        }
                    } else {
                        Log::error('订单已处理' . $orderInfo['out_trade_no']);
                    }
//                    flock($f, LOCK_UN);
//                    fclose($f);
//                }
            } catch (Exception $e){
                model('OrderPre')->rollback();
                model('Order')->rollback();
                model('OrderDet')->rollback();
                Log::error("订单处理异常1" . $orderInfo['out_trade_no'].":".$e->getMessage());
            }
        } else {
            Log::error('订单支付失败' . json_encode($_POST));
        }
        header("Content-Type:application/xml");
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    /**
     * 格式化支付返回信息
     * @param $orderInfo
     * @param $pay_type
     * @return array
     */
    private function formatRes($orderInfo)
    {
        $payInfo = [];
        $payInfo['out_trade_no'] = $orderInfo['out_trade_no'];
        $payInfo['trade_no'] = $orderInfo['transaction_id'];
        $payInfo['total_money'] = $orderInfo['total_fee'] / 100;
        return $payInfo;
    }


}