<?php

namespace app\common\model;

use think\Log;

require_once VENDOR_PATH . 'WeiXin/WxPay.Api.php';


class WeiXinPay
{

    /**
     * 统一下单
     * @param $orderInfo
     * @param $notify_url
     * @return mixed
     */
    public function createPrePayOrder($orderInfo, $notify_url)
    {

        $inputObj = new \WxPayUnifiedOrder();
        $inputObj->SetBody($orderInfo['subject']);
        $inputObj->SetDetail($orderInfo['body']);
        $inputObj->SetOut_trade_no($orderInfo['out_trade_no']);
        $inputObj->SetTotal_fee($orderInfo['total_amount'] * 100);
        $inputObj->SetNotify_url($notify_url);
        $inputObj->SetTrade_type($orderInfo['trade_type']);
        if ($orderInfo['trade_type'] == "JSAPI") {
            $inputObj->SetOpenid($orderInfo['open_id']);
        }
        try {
            $orderString = \WxPayApi::unifiedOrder($inputObj);
            Log::error($orderString);
            return $orderString;
        } catch (\Exception $e) {
            Log::error('微信支付异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 支付回调校验
     * @return array|bool|string
     */
    public function chargeNotify()
    {
        $result = \WxPayApi::notify();
//        if($result === false){
//            return '签名错误';
//        }else{
        return $result;
//        }

    }

    /**
     * 退款
     */
    public function refund($order)
    {
        $inputObj = new \WxPayRefund();
        $inputObj->SetTransaction_id($order['trade_no']);
        $inputObj->SetOut_trade_no($order["order_no"]);
        $inputObj->SetOut_refund_no($order['refund_id']);
        $inputObj->SetTotal_fee($order['total_money'] * 100);
        $inputObj->SetRefund_fee($order['refund_money'] * 100);
        $inputObj->SetOp_user_id($order['shop_id']);
        $inputObj->SetRefundDesc($order['refund_desc']);
        try {
            $result = \WxPayApi::refund($inputObj);
            Log::error('微信退款记录' . json_encode($result));
            if ($result['return_code'] == "SUCCESS" && $result['result_code'] == "SUCCESS") {
                return true;
            } else {
                Log::error("微信退款失败：" . $result["err_code_des"]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("微信退款失败：" . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取RSA公钥
     */
    public static function getPublicKey()
    {
        if (file_exists(__PUBLIC__ . "/public.pem")) {
            $pub_key = file_get_contents(file_exists(__PUBLIC__ . "/public.pem"));
            return $pub_key;
        } else {
            $f = fopen(__PUBLIC__ . "/public.pem", "w");
        }
        $inputObj = new \WxGetPublic();
        try {
            $result = \WxPayApi::getPublicKey($inputObj);
            if ($result['return_code'] == "SUCCESS" && $result['result_code'] == "SUCCESS") {
                $key = $result["pub_key"];
                fwrite($f, $key);
                fclose($f);
                return $key;
            } else {
                Log::error("获取公钥失败：" . $result["err_code_des"]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("获取公钥失败" . $e->getMessage());
            return false;
        }
    }

    /**
     * 付款到微信零钱
     */
    public function withdraw($order)
    {
        $inputObj = new \WxMchPay();
        $inputObj->SetOpenid($order["open_id"]);
        $inputObj->SetAmount($order["amount"] * 100);
        $inputObj->SetCheckName($order["check_name"]);
        $inputObj->SetDesc($order["desc"]);
        $inputObj->SetPartnerTradeNo($order["order_no"]);
        try {
            $result = \WxPayApi::mchPay($inputObj);
            Log::error("企业付款到零钱记录" . json_encode($result));
            if ($result['return_code'] == "SUCCESS" && $result['result_code'] == "SUCCESS") {
                return $result;
            } else {
                Log::error("企业支付失败：" . $result["err_code_des"]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error("企业付款异常：" . $e->getMessage());
            return false;
        }
    }

    /**
     *
     * 提现到银行卡
     * @param array $order 提现基本信息
     * @return bool|mixed
     */
    public function withdrawBank($order)
    {
        $pub_path = __PUBLIC__."/public.pem";
        if(!file_exists($pub_path)){
            WeiXinPay::getPublicKey();
        }
        $rsa = new \RSA($pub_path);
        $true_name = $rsa->encrypt($order["true_name"], "base64", 4);
        $bank_no = $rsa->encrypt($order["bank_no"], "base64", 4);
        $inputObj = new \WxMchPayBank();
        $inputObj->SetAmount($order["amount"] * 100);
        $inputObj->SetDesc($order["desc"]);
        $inputObj->SetPartnerTradeNo($order["order_no"]);
        $inputObj->SetBankCode($order["bank_code"]);
        $inputObj->SetBankNo($bank_no);
        $inputObj->SetTrueName($true_name);
        try {
            $result = \WxPayApi::mchPayBank($inputObj);
            Log::error("企业付款到银行卡记录" . json_encode($result));
            if ($result['return_code'] == "SUCCESS" && $result['result_code'] == "SUCCESS") {
                return $result;
            } else {
                Log::error("企业支付到银行卡失败：" . $result["err_code_des"]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error("企业付款到银行卡异常：" . $e->getMessage());
            return false;
        }
    }

}