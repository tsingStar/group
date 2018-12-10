<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/10/24
 * Time: 11:29
 */

class ImageDraw
{
    /**
     * @param array $product 商品数组
     * @param array $leader 团长信息
     * @param array $group 团信息
     * @return string
     */
    public static function drawImage($product, $leader, $group)
    {
        $image = imagecreatetruecolor(1080, 1728);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        //获取图片类型  添加商品图片
        $good_image = $product["image"];
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
        list($good_width, $good_height) = getimagesize($good_image);
        imagecopyresized($image, $p_img, 0, 0, 0, 0, 1080, 1080, $good_width, $good_height);

        //添加头像
        // 填充背景色
        $col_ellipse = imagecolorallocate($image, 0, 147, 185);
        //1。添加头像边框
        imagefilledellipse($image, 120, 120, 160, 160, $col_ellipse);
        //2.添加团长昵称边框
        $header_name = $leader["name"];
        $header_size = self::getFontLen(40, $header_name);
        $x1 = 170;
        $y1 = 85;
        $x2 = $x1 + $header_size + 60;
        $y2 = 165;
        $radius = 20;
        self::drawRadiusRec($image, $x1, $y1, $x2, $y2, $radius, $col_ellipse);

        //添加团长昵称
        imagefttext($image, 40, 0, 220, 150, $white, __PUBLIC__ . '/MicrosoftYahei.ttf', $header_name);

        //3.添加头像
        $header_image = imagecreatefromstring(file_get_contents($leader["header_image"]));

        list($header_width, $header_height) = getimagesize($leader["header_image"]);
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
        $good_price = sprintf("%.2f", $product["group_price"]);
        $len = self::getFontLen(100, $good_price);
        self::drawRadiusRec($image, 40, 1100, 40 + $len + 40, 1260, 20, $col_ellipse);
        imagefttext($image, 100, 0, 60, 1230, $white, __PUBLIC__ . '/MicrosoftYahei.ttf', $good_price);

        $font_color = imagecolorallocate($image, 51, 51, 51);
        //添加团购标题
        $group_title = $group["title"];
        $title_len = self::getFontLen(40, $group_title);
        $font_size = round($title_len / mb_strlen($group_title));
        if ($title_len > 860) {
            $font_num = round(860 / $font_size);
            $group_title = mb_substr($group_title, 0, $font_num - 3);
            $group_title .= "...";
            $title_len = 860;
        }
        $title_color = imagecolorallocatealpha($image, 255, 255, 255, 64);
        self::drawRadiusRec($image, 1080 - $title_len - 30, 770, 1180, 870, 20, $title_color);
        imagefttext($image, 40, 0, 1080 - $title_len-20, 845, $font_color, __PUBLIC__ . '/MicrosoftYahei.ttf', $group_title);

        //商品名称
        $good_name = $product["product_name"];
        $name_len = self::getFontLen(40, $good_name);
        $font_size = round($name_len / mb_strlen($good_name));
        if ($name_len > 860) {
            $font_num = round(860 / $font_size);
            $good_name = mb_substr($good_name, 0, $font_num - 3);
            $good_name .= "...";
            $name_len = 860;
        }
        self::drawRadiusRec($image, 1080 - $name_len - 40, 920, 1180, 1020, 20, $title_color);
        imagefttext($image, 40, 0, 1080 - $name_len - 35, 995, $font_color, __PUBLIC__ . '/MicrosoftYahei.ttf', $good_name);
//
//        //商品描述
        $good_desc = $product["product_desc"];
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
        $group_id = $group["id"];
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $output = curl_exec($ch);
        curl_close($ch);
        if(!is_null(json_decode($output))){
            cache("access_token", null);
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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $output = curl_exec($ch);
            curl_close($ch);
        }
        $qr_code = imagecreatefromstring($output);
        list($qr_code_width, $qr_code_height) = getimagesizefromstring($output);
        imagecopyresized($image, $qr_code, 750, 1428, 0, 0, 300, 300, $qr_code_width, $qr_code_height);
        imagejpeg($image, __UPLOAD__ . "/erweima/" . $group_id . "-" . $product["id"] . ".jpg", 40);
        imagedestroy($image);
        return "https://" . __URI__ . "/upload/erweima/" . $group_id . "-" . $product["id"] . ".jpg";
    }

    public static function drawRadiusRec(&$image, $x1, $y1, $x2, $y2, $radius, $col_ellipse)
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
        imagefilledarc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $col_ellipse, IMG_ARC_PIE);
        imagefilledarc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $col_ellipse, IMG_ARC_PIE);
        imagefilledarc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $col_ellipse, IMG_ARC_PIE);
        imagefilledarc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $col_ellipse, IMG_ARC_PIE);
    }

    //获取文字所占长度
    public static function getFontLen($font_size, $string)
    {
        $header_size = imagettfbbox($font_size, 0, __PUBLIC__ . '/MicrosoftYahei.ttf', $string);
        return $header_size[2] - $header_size[0];
    }

}