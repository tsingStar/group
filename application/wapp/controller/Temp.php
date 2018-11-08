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


    function test1()
    {
        $group_id = input("group_id");
        $product_id = input("product_id");
        $leader_id = input("leader_id");
        $group = model("Group")->where("id", $group_id)->find();
        $product = model("GroupProduct")->where("id", $product_id)->find();
        $product["image"] = model("HeaderGroupProductSwiper")->where("header_group_product_id", $product["header_product_id"])->order("swiper_type")->value("swiper_url");
        $leader = model("User")->where("id", $leader_id)->field("user_name name, avatar header_image")->find();
        $image_url = \ImageDraw::drawImage($product, $leader, $group);
        exit_json(1, "请求成功", ["imgUrl"=>$image_url]);
    }

    public function drawImage()
    {
        $image = imagecreatetruecolor(1080, 1728);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        //获取图片类型  添加商品图片
        $good_image = "http://www.ybt9.com/upload/20181016/47dc86ad67f77cb781a8202de1390f3e.jpg";
        $default_image = "";
        $ext = getimagesize($good_image);
        switch ($ext["mime"]) {
            case "image/png":
                $p_img = imagecreatefrompng($good_image);
                break;
            case "image/jpg":
                $p_img = imagecreatefromjpeg($good_image);
                break;
            case "image/jpeg":
                $p_img = imagecreatefromjpeg($good_image);
                break;
            default:
                $p_img = imagecreatefrompng($default_image);
        }
        imagecopy($image, $p_img, 0, 0, 0, 0, 1080, 1080);

        //添加头像
        // 填充背景色
        $col_ellipse = imagecolorallocate($image, 0, 147, 185);
        //1。添加头像边框
        imagefilledellipse($image, 120, 120, 160, 160, $col_ellipse);
        //2.添加团长昵称边框
        $header_name = "大帅锅cxgsdfv是德";
        $header_size = $this->getFontLen(40, $header_name);
        $x1 = 170;
        $y1 = 85;
        $x2 = $x1 + $header_size + 60;
        $y2 = 165;
        $radius = 20;
        $this->drawRadiusRec($image, $x1, $y1, $x2, $y2, $radius, $col_ellipse);

        //添加团长昵称
        imagefttext($image, 40, 0, 220, 150, $white, __PUBLIC__ . '/MicrosoftYahei.ttf', $header_name);

        //3.添加头像
        $header_image = imagecreatefromstring(file_get_contents("https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTKxbXEP8NNU0Y1FJhPLPoQQsTQE3YgpJP6REJu2zXc4icYIJib9o4LoZ42l0oicsZKgoDHQiaUaNAW1Ew/132"));

        list($header_width, $header_height) = getimagesize("https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTKxbXEP8NNU0Y1FJhPLPoQQsTQE3YgpJP6REJu2zXc4icYIJib9o4LoZ42l0oicsZKgoDHQiaUaNAW1Ew/132");
        $w = min($header_height, $header_width);
        $h = $w;
        $newpic = imagecreatetruecolor($w, $h);
        imagealphablending($newpic, false);
        $transparent = imagecolorallocatealpha($newpic, 0, 0, 0, 127);
        $r = $w / 2;
        for ($x = 0; $x < $w; $x++)
            for ($y = 0; $y < $h; $y++) {
                $c = imagecolorat($header_image, $x, $y);
                $_x = $x - $w / 2;
                $_y = $y - $h / 2;
                if ((($_x * $_x) + ($_y * $_y)) < ($r * $r)) {
                    imagesetpixel($newpic, $x, $y, $c);
                } else {
                    imagesetpixel($newpic, $x, $y, $transparent);
                }
            }
        imagesavealpha($newpic, true);
        imagecopy($image, $newpic, 55, 55, 0, 0, $w, $h);
        imagedestroy($newpic);

        //添加商品价格
        $good_price = sprintf("%.2f", 15.58);
        $len = $this->getFontLen(100, $good_price);
        $this->drawRadiusRec($image, 40, 1100, 40 + $len + 40, 1260, 20, $col_ellipse);
        imagefttext($image, 100, 0, 60, 1230, $white, __PUBLIC__ . '/MicrosoftYahei.ttf', $good_price);

        $font_color = imagecolorallocate($image, 51, 51, 51);
        //添加团购标题
        $group_title = "优鲜团-优选天下团";
        $title_len = $this->getFontLen(40, $group_title);
        $font_size = round($title_len / mb_strlen($group_title));
        if ($title_len > 860) {
            $font_num = round(860 / $font_size);
            $group_title = mb_substr($group_title, 0, $font_num - 3);
            $group_title .= "...";
            $title_len = 860;
        }
        $title_color = imagecolorallocatealpha($image, 255, 255, 255, 64);
        $this->drawRadiusRec($image, 1080 - $title_len - 20, 770, 1180, 870, 20, $title_color);
        imagefttext($image, 40, 0, 1080 - $title_len, 845, $font_color, __PUBLIC__ . '/MicrosoftYahei.ttf', $group_title);

        //商品名称
        $good_name = "超细纤维毛巾 2条/份sdf但是烦恼呢那份翻了翻你阿苏丹诺夫啦额午饭路径是你德飞了阿就不说多了阿部分为阿善良德飞";
        $name_len = $this->getFontLen(40, $good_name);
        $font_size = round($name_len / mb_strlen($good_name));
        if ($name_len > 860) {
            $font_num = round(860 / $font_size);
            $good_name = mb_substr($good_name, 0, $font_num - 3);
            $good_name .= "...";
            $name_len = 860;
        }
        $this->drawRadiusRec($image, 1080 - $name_len - 20, 920, 1180, 1020, 20, $title_color);
        imagefttext($image, 40, 0, 1080 - $name_len, 995, $font_color, __PUBLIC__ . '/MicrosoftYahei.ttf', $good_name);
//
//        //商品描述
        $good_desc = "我司想好似德飞呢我就sdfsadgasdf从你的少女啦你路径了老师德飞路径sdfasdgfasdfsdfssdfs安德飞了今年劳动节撒妇女林彼此啊千百次不过分  阿善良的空间分辨率阿部分 i 女人按时到了觉得舒服呢拉德斯基你发了确认办法";
        $start_x = 40;
        $start_y = 1320;
        $str_arr = preg_split('/(?<!^)(?!$)/u', $good_desc);
        $index = 0;
        foreach ($str_arr as $val) {
            $font_con = imagefttext($image, 30, 0, $start_x, $start_y, $font_color, __PUBLIC__ . '/MicrosoftYahei.ttf', $val);
            $start_x = max($font_con[2], $font_con[4]) + 5;
            if ($start_x + 5 > 1000) {
                $index += 1;
                $start_y = $start_y + 60;
                $start_x = 40;
            }
            if ($index == 1 && $start_x + 5 > 950) {
                imagefttext($image, 30, 0, $start_x, $start_y, $font_color, __PUBLIC__ . '/MicrosoftYahei.ttf', "...");
                break;
            }
        }

        //添加分割线
        $style = array($font_color, $font_color, $font_color, $font_color, $font_color, $white, $white, $white, $white, $white);
        imagesetstyle($image, $style);
        imageline($image, 0, 1400, 1080, 1400, IMG_COLOR_STYLED);


        //二维码简述
        $font_color1 = imagecolorallocate($image, 102, 102, 102);
        imagefttext($image, 40, 0, 40, 1510, $font_color1, __PUBLIC__ . '/MicrosoftYahei.ttf', "长按识别或扫描二维码");
        imagefttext($image, 40, 0, 40, 1620, $font_color1, __PUBLIC__ . '/MicrosoftYahei.ttf', "更多优质商品等您挑选");

        //添加二维码
        $group_id = 41;
        $access_token = getAccessToken();
        $ch = curl_init();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $post_data = [
            "scene" => $group_id,
            "page" => "pages/members/membersDetails/membersDetails"
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        // 请求头，可以传数组
//        curl_setopt($ch, CURLOPT_HEADER, $header);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $output = curl_exec($ch);
        curl_close($ch);
        $qr_code = imagecreatefromstring($output);
        list($qr_code_width, $qr_code_height) = getimagesizefromstring($output);
        imagecopyresized($image, $qr_code, 750, 1428, 0, 0, 300, 300, $qr_code_width, $qr_code_height);
        header("content-type:image/png");
        imagejpeg($image, __UPLOAD__."/erweima/".$group_id."-22.jpg", 40);
//        imagejpeg($image, "./".$group_id."-22.jpg", 5);
        imagedestroy($image);
        exit();

    }

    function drawRadiusRec(&$image, $x1, $y1, $x2, $y2, $radius, $col_ellipse)
    {
        //顶部矩形
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y1 + $radius, $col_ellipse);
        //右边矩形
        imagefilledrectangle($image, $x2 - $radius, $y1 + $radius, $x2, $y2 - $radius, $col_ellipse);
        //下边矩形
        imagefilledrectangle($image, $x1 + $radius, $y2 - $radius, $x2 - $radius, $y2, $col_ellipse);
        //左边矩形
        imagefilledrectangle($image, $x1, $y1 + $radius, $x1 + $radius, $y2 - $radius, $col_ellipse);
        //中间矩形
        imagefilledrectangle($image, $x1 + $radius, $y1 + $radius, $x2 - $radius, $y2 - $radius, $col_ellipse);

//         draw circled corners  添加圆角
        imagefilledarc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $col_ellipse, IMG_ARC_EDGED);
        imagefilledarc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $col_ellipse, IMG_ARC_EDGED);
        imagefilledarc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $col_ellipse, IMG_ARC_EDGED);
        imagefilledarc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $col_ellipse, IMG_ARC_EDGED);
    }

    //获取文字所占长度
    function getFontLen($font_size, $string)
    {
        $header_size = imagettfbbox($font_size, 0, __PUBLIC__ . '/MicrosoftYahei.ttf', $string);
        return $header_size[2] - $header_size[0];
    }


    public function subcribe()
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1", "6379");
        $redis->auth("tsing");
        $redis->subscribe(["chat1"], "subcall");
    }

    function subcall($redis, $chan, $msg)
    {
        Log::error("123123123123123");
    }

    public function pubs()
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1", "6379");
        $redis->auth("tsing");
        $redis->publish("chat1", "woshi chat");

    }


    /**
     * 测试方法
     */
    public function test()
    {
        Log::error(input("msg"));
    }

    /**
     *
     */
    public function getZSet()
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1", "6379");
        $redis->auth("tsing");
        $list = $redis->zRevRange("group_1", 0, -1);
        foreach ($list as $value) {
            $value = json_decode($value, true);
            echo $value["user_name"];
        }
    }


}