<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/22
 * Time: 9:31
 */

namespace app\common\model;


class SendSms
{
    private $ServerApi;
    private $sendType;
    private $error;
    private $phone;
    private $tempId;

    public function __construct($phone, $tempId='', $sendType = '')
    {
        vendor('netease/ServerApi');
        $this->ServerApi = new \ServerAPI(config('netease.appKey'), config('netease.appSecret'));
        $this->phone = $phone;
        $this->sendType = $sendType;
        $this->tempId = $tempId;
    }

    /**
     * 发送验证码
     * @return bool
     */
    public function sendVcode()
    {
        $res = $this->ServerApi->sendSmsCode($this->tempId, $this->phone);
        if ($res['code'] === 200) {
            return true;
        } else {
            $this->error = $res['msg'];
            return false;
        }
    }

    /**
     * 发送模板消息
     * @param $param
     */
    public function sendTemplate($param)
    {
        $res = $this->ServerApi->sendSMSTemplate($this->tempId, $this->phone, $param);
        if ($res['code'] === 200) {
            return true;
        } else {
            $this->error = $res['msg'];
            return false;
        }
        
    }

    /**
     * 检验验证码
     * @param $vcode
     * @return bool
     */
    public function checkVcode($vcode)
    {
        $res = $this->ServerApi->verifycode($this->phone, $vcode);
        if($res['code'] === 200){
            return true;
        }else{
            $this->error = $res['code'];
            return false;
        }
    }

    public function getError()
    {
        return $this->error;
    }
}