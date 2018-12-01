<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
include 'extend.php';
/**
 * 设置管理员登陆
 * @param $admin
 */
function set_admin_login($admin)
{
    session('admin', $admin);
    session('role_id', $admin['role_id']);
    session('admin_id', $admin['id']);
}

/**
 * 获取树级目录
 * @param $a
 * @param $pid
 * @param $col
 * @return array
 */
function getTree($a, $pid, $col = 'parent_id')
{
    $tree = array();                                //每次都声明一个新数组用来放子元素
    foreach ($a as $v) {
        if (!$v['name']) {
            $v['name'] = "";
        }
        if ($v["$col"] == $pid) {                      //匹配子记录
            $v['children'] = getTree($a, $v['id'], $col); //递归获取子记录
            if ($v['children'] == null) {
//                unset($v['children']);             //如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
            }
            $tree[] = $v;                           //将记录存入新数组
        }
    }
    return $tree;                                  //返回新数组
}

/**
 * 目录处理 去除没有子目录的主目录
 */
function getMenu($list)
{
//    foreach ($list as $key=>$item) {
//        foreach ($item as $k=>$v){
//            if(!isset($v['children'])){
//                unset($item[$k]);
//                continue;
//            }
//        }
//    }
    return $list;
}

/**
 * 添加登陆日志
 */
function addAdminLoginLog()
{
    $webLog = new \app\admin\model\WebLoginLog();
    $webLog->saveLog();
}

/**
 * 添加操作记录
 */
function addAdminOperaLog()
{
    $webLog = new \app\admin\model\WebOperaLog();
    $webLog->saveLog();
}


/**
 * 格式化返回数据
 * @param int $code
 * @param string $msg
 * @param object $data
 */
function exit_json($code = 1, $msg = "操作成功", $data = null)
{
    header('Content-Type:application/json; charset=utf-8');
    exit(json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]));
}

/**
 * 校验手机号合法性
 * @param $telephone
 * @return false|int
 */
function test_tel($telephone)
{
    return preg_match('/^1[34578]\d{9}$/', $telephone);
}

/**
 * 验证码
 */
function captcha_src()
{


}

/**
 * 遍历指定目录下所有文件
 * @param $path
 * @return array
 */
function getAllFiles($path)
{
    $arr = [];
    if (is_dir($path)) {
        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                if (!is_dir($file)) {
                    $filePath = str_replace(__PUBLIC__, '', $path);
                    $arr[] = $filePath . '/' . $file;
                }
            }
            closedir($dh);
        }
    }
    return $arr;
}

/**
 * 加密id
 * @param $id
 * @return mixed
 */
function encodeId($id)
{

    return $id;
}

/**
 * 解析id
 * @param $enid
 * @return mixed
 */
function decodeId($enid)
{
    return $enid;
}

/**
 * 发送推送消息
 * @param string $receive 接受人 默认all 平台广播  根据registrationId推  array("registration_id"=>array("160a3797c800569cd95","160a3797c800569cd95"))
 * @param string $title App名称
 * @param string $content 内容
 * @param int $is_notify 是否是通知
 * @param array $extras 自定义信息
 * @param int $m_time 离线保留时间
 * @return string
 */
function pushMess($content = "显示内容", $extras = array(), $receive = "all", $title = "", $is_notify = 1, $m_time = 86400)
{
    vendor('JPush.Jpush');
    $pushObj = new \Jpush(config('jiguangKey'), config('jiguangSecret'));
    //调用推送,并处理
    $result = $pushObj->push($receive, $title, $content, $extras, $m_time, $is_notify);
    if ($result) {
        $res_arr = json_decode($result, true);
        if (isset($res_arr['error'])) {   //如果返回了error则证明失败
            //错误信息 错误码
            return $res_arr['error']['message'] . '：' . $res_arr['error']['code'];
        } else {
            //处理成功的推送......
            //可执行一系列对应操作~
            return true;
        }
    } else {      //接口调用失败或无响应
        return 404;
    }
}

/**
 * 删除文件
 * @param $path
 * @return bool
 */
function delfile($path)
{
    //如果是目录则继续
    if (is_dir($path)) {
        //扫描一个文件夹内的所有文件夹和文件并返回数组
        $p = scandir($path);
        foreach ($p as $val) {
            //排除目录中的.和..
            if ($val != "." && $val != "..") {
                //如果是目录则递归子目录，继续操作
                if (is_dir($path . $val)) {
                    //子目录中操作删除文件夹和文件
                    delfile($path . $val . '/');
                    //目录清空后删除空文件夹
                    @rmdir($path . $val . '/');
                } else {
                    //如果是文件直接删除
                    @unlink($path . $val);
                }
            }
        }
    } else {
        @unlink($path);
    }
}

/**
 * 获取客户端真实Ip
 * @return array|false|string
 */
function getIp()
{

    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknow")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknow")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknow")) {
        $ip = getenv("REMOTE_ADDR");
    } else if (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknow")) {
        $ip = $_SERVER["REMOTE_ADDR"];
    } else {
        $ip = "unknow";
    }
    return $ip;

}

/**
 * 获取指定经纬度之间距离
 * @param $lat1
 * @param $lng1
 * @param $lat2
 * @param $lng2
 * @param int $len_type
 * @param int $decimal
 * @return float
 */
function GetDistance($lat1, $lng1, $lat2, $lng2, $len_type = 1, $decimal = 2)
{

    $earth = 6378.137;
    $radLat1 = $lat1 * PI() / 180.0;   //PI()圆周率
    $radLat2 = $lat2 * PI() / 180.0;
    $a = $radLat1 - $radLat2;
    $b = ($lng1 * PI() / 180.0) - ($lng2 * PI() / 180.0);
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $s = $s * $earth;
    $s = round($s * 1000);
    if ($len_type > 1) {
        $s /= 1000;
    }
    return round($s, $decimal);
}

//获取目录下文件（不包含子目录）
function showdir($gno, $position = 'ontop')
{
    $filedir = array();
    $path = __UPLOAD__ . "/goodsimg/$gno/$position";
    $remote_path = "/upload/goodsimg/$gno/$position";
    if (!is_dir($path)) {
        return $filedir;
    }
    $dh = opendir($path);//打开目录
    while (($d = readdir($dh)) != false) {
        //逐个文件读取，添加!=false条件，是为避免有文件或目录的名称为0
        if ($d == '.' || $d == '..') {//判断是否为.或..，默认都会有
            continue;
        }
        if (is_dir($path . '/' . $d)) {//如果为目录
            //showdir($path.'/'.$d);//继续读取该目录下的目录或文件
            continue;
        }
        $filedir[] = $remote_path . '/' . $d;
    }
    sort($filedir);
    return $filedir;
}

/**
 * 获取系统订单编号
 */
function getOrderNo()
{
    $millisecond = get_millisecond();
    $millisecond = str_pad($millisecond, 3, '0', STR_PAD_RIGHT);
    return date("YmdHis") . $millisecond . rand(1000, 9999);
}

/**
 * microsecond 微秒     millisecond 毫秒
 *返回时间戳的毫秒数部分
 */
function get_millisecond()
{
    list($usec, $sec) = explode(" ", microtime());
    $msec = round($usec * 1000);
    return $msec;

}


/**
 * Excel一键导出
 */
function excel($header, $data, $filename, $num = 1, $extraTitle=[])
{
    $error = \Excel::export($header, $data, $filename, '2007', $num, $extraTitle);
    return $error;
}

/**
 * 二位数组去重
 * @param $arr
 * @return array
 */
function arr_unique($arr)
{
    $data = [];
    foreach ($arr as $a) {
        $data[] = json_encode($a);
    }
    $data = array_unique($data);
    $list = [];
    foreach ($data as $t) {
        $list[] = json_decode($t, true);
    }
    return $list;
}


/**
 * 上传文件，支持单、多文件
 * @param $name
 * @return array|bool|string
 */
function uploadFile($name)
{
    $file = request()->file($name);
    if ($file) {
        if (is_array($file)) {
            $file_url = [];
            foreach ($file as $item) {
                $info = $item->move(__UPLOAD__);
                $saveName = $info->getSaveName();
                $path = "https://www.ybt9.com/upload/" . $saveName;
                $file_url[] = $path;
            }
            $result_url = $file_url;
        } else {
            $info = $file->move(__UPLOAD__);
            $saveName = $info->getSaveName();
            $path = "https://www.ybt9.com/upload/" . $saveName;
            $result_url = $path;
        }
    } else {
        $result_url = false;
    }
    return $result_url;
}

function getAccessToken()
{
    if (cache("access_token")) {
        return cache("access_token");
    } else {
        $res = json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . config("weixin.app_id") . "&secret=" . config("weixin.app_secret")), true);
        if (!isset($res["access_token"])) {
            return false;
        } else {
            cache("access_token", $res["access_token"], $res["expires_in"]);
            return $res["access_token"];
        }
    }
}

/**
 * 发送模版消息
 * @param $order
 */
function sendTemplate($order)
{
    $access_token = getAccessToken();
    $data = [
        "touser"=>$order["open_id"],
        "template_id"=>$order["template_id"],
        "page"=>$order["page"],
        "form_id"=>$order["form_id"],
        "data"=>[
            "keyword1"=>[
                "value"=>$order["receive_address"],
            ],
            "keyword2"=>[
                "value"=>$order["order_no"]
            ],
            "keyword3"=>[
                "value"=>$order["product_list"]
            ],
            "keyword4"=>[
                "value"=>$order["order_money"]
            ],
            "keyword5"=>[
                "value"=>$order["tips"]
            ]
        ]
    ];
    $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token;
    $res = curl_request($url, json_encode($data));
}

/**
 * 请求
 * @param $url
 * @param $param
 * @param $time_out
 * @return mixed
 */
function curl_request($url, $param="", $time_out=30)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($param != '') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    }
    if($time_out>0 && $time_out<1){
        curl_setopt($ch, CURLOPT_NOSIGNAL,1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS,$time_out*1000);
    }else{
        curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
    }
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $file_contents = curl_exec($ch);
    curl_close($ch);
    return $file_contents;
}


function logs($con){
    $f = fopen(__PUBLIC__."/logs.txt", "a+");
    fwrite($f, $con."\n");
    fclose($f);
}

function logs1($con){
    $f = fopen(__PUBLIC__."/logs1.txt", "a+");
    fwrite($f, $con."\n");
    fclose($f);
}





