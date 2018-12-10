<?php

class Ffmpeg
{

    public static function getVideoInfo($file)
    {
        $re = array();
        exec("ffmpeg -i {$file} 2>&1", $re);
        $info = implode("\n", $re);
        if (preg_match("/Invalid data/i", $info)) {
            return false;
        }

        $match = array();
        preg_match("/\d{2,}x\d+/", $info, $match);
        list($width, $height) = explode("x", $match[0]);

        $match = array();
        preg_match("/Duration:(.*?),/", $info, $match);
        $duration = date("H:i:s", strtotime($match[1]));

        $match = array();
        preg_match("/bitrate:(.*kb\/s)/", $info, $match);
        $bitrate = $match[1];

        if (!$width && !$height && !$duration && !$bitrate) {
            return false;
        } else {
            return array(
                "width" => $width,
                "height" => $height,
                "duration" => $duration,
                "bitrate" => $bitrate,
            );
        }
    }

    public static function createImage($video_file, $image_file, $width = 0, $height = 0, $offset_sec = 0)
    {
        $re = array();
        $info = false;
        if ($width && !$height) {
            if (!$info) {
                $info = self::getVideoInfo($video_file);
            }
            $width = min($width, $info['width']);
            $height = intval($width / $info['width'] * $info['height']);
        }
        if (!$width && $height) {
            if (!$info) {
                $info = self::getVideoInfo($video_file);
            }
            $height = min($height, $info['height']);
            $width = intval($height / $info['height'] * $info['width']);
        }
        if ($offset_sec) {
            if (!$info) {
                $info = self::getVideoInfo($video_file);
            }
            $max_sec = strtotime($info['duration']) - strtotime(date("Y-m-d"));
            $offset_sec = min($offset_sec, $max_sec);
            $ss = " -ss {$offset_sec} ";
        }

        if ($width && $height) {
            $s = " -s {$width}x{$height} ";
        }
        $com = "ffmpeg -i {$video_file} -y -f image2 -vframes 1 {$ss} {$s} {$image_file} 2>&1";
        exec($com, $re);
        $r = array_pop($re);
        preg_match("/video:(\d*)kB/i", $r, $match);
        if (intval($match[1])) {
            return true;
        } else {
            return false;
        }
    }
}
