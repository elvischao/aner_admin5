<?php

# 自定义返回代码


const SUCCESS_CODE = 200;
const ERROR_CODE = 500;

/**
 * 返回异常回调信息
 *
 * @param string $msg 返回信息
 * @param array $data 返回数据
 * @return void
 */
function error(string $msg, array $data = []){
    return return_data(ERROR_CODE, $msg, $data);
}

/**
 * 返回成功回调信息
 *
 * @param string $msg 返回信息
 * @param array $data 返回数据
 * @return void
 */
function success(string $msg, array $data = []){
    return return_data(SUCCESS_CODE, $msg, $data);
}

/**
 * 手动抛出异常，用于中断程序
 *
 * @param string $msg 返回信息
 * @return void
 */
function throwBusinessException(string $msg){
    throw new \App\Exceptions\BusinessException($msg);
}

/**
 * 接口返回json格式的数据
 *
 * @param integer $code 项目自定义错误码
 * @param string $msg 返回信息
 * @param array $data 返回数据
 * @return void
 */
function return_data(int $code, string $msg, array $data){
    return response()->json(['code'=> $code, 'msg'=> $msg, 'data'=> $data], 200); # 此 200 为真正的 http 状态码
}

/**
 * 生成随机码
 *
 * @param int $number 随机码位数
 * @param string $type 随机码内容类型
 * @return string
 */
function create_captcha(int $number, string $type = 'figure'):string{
    $array_figure = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
    $array_lowercase = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
    $array_uppercase = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    switch($type){
        case 'lowercase':
            $res_array = $array_lowercase;
            break;
        case 'uppercase':
            $res_array = $array_uppercase;
            break;
        case 'lowercase+figure':
            $res_array = array_merge($array_lowercase, $array_figure);
            break;
        case 'uppercase+figure':
            $res_array = array_merge($array_uppercase, $array_figure);
            break;
        case 'lowercase+uppercase':
            $res_array = array_merge($array_lowercase, $array_uppercase);
            break;
        case 'lowercase+uppercase+figure':
            $res_array = array_merge(array_merge($array_lowercase, $array_uppercase), $array_figure);
            break;
        default:
            $res_array = $array_figure;
            break;
    }
    $resstr = '';
    shuffle($res_array);
    foreach(array_rand($res_array, $number) as $v){
        $resstr .= $res_array[$v];
    }
    return $resstr;
}

/**
 * 将包含英文逗号的字符串(以英文逗号隔开的字符串参数)转换成数组
 *
 * @param string $str 以英文逗号隔开的字符串
 * @return Array
 */
function comma_str_to_array(string $str):Array {
    $array = explode(',', $str);
    $res = [];
    foreach($array as $value){  // 空字符串时, 上一步得到的结果为 ['']
        if($value != ''){
            $res[] = $value;
        }
    }
    return $res;
}

/**
 * 将数组转换为以英文逗号隔开的字符串
 *
 * @param array $array
 * @return string
 */
function array_to_comma_str(array $array):string {
    return implode(',', $array);
}

/**
 * 二维码生成
 *
 * @param string $url 链接
 * @param string $identity 标识(文件名)
 * @return void
 */
function qrcode($url, $identity){
    include_once '../extend/phpqrcode/qrlib.php'; //放在extend中
     //vendor('phpqrcode.phpqrcode'); //放在vender中
     $errorCorrectionLevel = 'H'; //容错级别
     $matrixPointSize = 5; //图片大小慢慢自己调整，只要是int就行
     $path = '../storage/qrcode/';
     $QR = $QRB = $path . $identity . ".png";
     QRcode::png($url, $QR, $errorCorrectionLevel, $matrixPointSize, 2);
     if(file_exists($path . $identity . ".png")){
         return config("app.url") . "/storage/qrcode/" . $identity . ".png";
     }else{
         return false;
     }
 }