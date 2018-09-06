<?php
/**
 * 应用访问日志
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/26
 * Time: 10:45
 */

namespace app\admin\model;


use think\Model;

class WebOperaLog extends Model
{


    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function setLoginIpAttr($value)
    {
        return ip2long($value);
    }
    public function getLoginIpAttr($value)
    {
        return long2ip($value);
    }

    /**
     * 保存应用访问记录
     */
    function saveLog()
    {
        $data['module'] = request()->module();
        $data['controller'] = request()->controller();
        $data['action'] = request()->action();
        $data['create_time'] = time();
        $data['login_ip'] = request()->ip();
        $data['data'] = json_encode($_POST);
        $data['admin_id'] = session('admin_id');
        $this->save($data);
    }

    /**
     * 获取网站操作记录
     */
    function getWebLog()
    {




    }


}