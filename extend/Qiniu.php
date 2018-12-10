<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/11/12
 * Time: 15:25
 */

require_once VENDOR_PATH . '/qiniu-sdk/autoload.php';
use think\config;
//引入七牛云的相关文件
use Qiniu\Auth as Auth;
use Qiniu\Storage\UploadManager;
class Qiniu
{
    const ACCESS_KEY = "7pG8GenMlviMSPaKQf8FE5C09oY1XXKOMMvvi4BI";
    const SECRET_KEY = "PIY6rsZ4SZehqEzAEtniXBVORhP3CqOgbMgwAZuB";
    const BUCKET = "yxt-tupian";
    const DOMAIN = "http://phyf590if.bkt.clouddn.com";
    public function __construct()
    {

    }

    /**
     * 上传图片/视频  文件上传
     * @param $file
     * @return array
     * @throws Exception
     */
    public static function UploadFile($file)
    {
        // 要上传图片的本地路径
        $filePath = $file->getRealPath();
        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
        // 上传到七牛后保存的文件名
        $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;

        // 构建鉴权对象
        $auth = new Auth(self::ACCESS_KEY, self::SECRET_KEY);
        // 要上传的空间
        $token = $auth->uploadToken(self::BUCKET);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            return ["err"=>1,"msg"=>$err,"data"=>""];
        } else {
            //返回图片的完整URL
            return ["err"=>0,"msg"=>"上传完成","data"=>(self::DOMAIN .'/'. $ret['key'])];
        }
    }

    /**
     * 上传二进制数据流
     * @param string $url 文件地址
     * @return mixed
     */
    public static function Upload($url)
    {

        $info = pathinfo($url);
        $ocstream = file_get_contents($url);
        $key = $info["basename"];
        // 构建鉴权对象
        $auth = new Auth(self::ACCESS_KEY, self::SECRET_KEY);
        // 要上传的空间
        $token = $auth->uploadToken(self::BUCKET);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->put($token, $key, $ocstream);
        if ($err !== null) {
            return ["err"=>1,"msg"=>$err,"data"=>""];
        } else {
            //返回图片的完整URL
            return ["err"=>0,"msg"=>"上传完成","data"=>(self::DOMAIN .'/'. $ret['key'])];
        }
        
    }

    /**
     * 删除图片/视频
     * @param string $key 文件名称包含后缀
     * @return mixed
     */
    public static function delFile($key)
    {
        $auth = new Auth(self::ACCESS_KEY, self::SECRET_KEY);
        $config = new \Qiniu\Config();
        $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
        $err = $bucketManager->delete(self::BUCKET, $key);
        if($err){
            return $err;
        }else{
            return false;
        }

    }


}