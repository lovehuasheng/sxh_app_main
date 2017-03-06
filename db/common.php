<?php
    include "../db/pdo.php";
    include "../extend/org/RedisLib.php";
    $matchhelp_out_time = 86400;/*默认超时时间*/
    //特殊返回状态码
    $request_code = array(
    );
    function set_password($pwd,$security='') {
        $public_key = md5('FR4ehHBBbjD7ZBNEv_GCvXBsmNSq0zLV');
        return md5(sha1($pwd . $security) . $public_key);
    };
    /*新用户二级密码加密*/
    function set_secondary_password($pwd) {
        $pwd = md5($pwd);
        $public_key = md5('FR4ehHBBbjD7ZBNEv_GCvXBsmNSq0zLV');
        return md5(sha1($pwd . '') . $public_key);
    };
    /*老用户登录密码以及二级密码加密*/
    function set_old_password($pwd) {
        return md5($pwd);
    }
/**
 * 取七牛图片地址
 * @param type $pic
 * @param type $w
 * @param type $h
 * @return type
 */
function getQiNiuPic($path, $w = 0, $h = 0) {
    $url = 'http://' . config('qiniu.baseUrl') . '/';
    $picture = new \org\upload\driver\qiniu\QiniuStorage(config('qiniu'));
    return $picture->privateDownloadUrl($url . $path . "?imageView2/1/w/{$w}/h/{$h}");
}
/**
 * 根据时间获取表的后缀，即按季度分表
 * $nowtime 时间戳
 * return array
 */
function getTable($nowtime,$order=0){
    if($nowtime>time()){
        $nowtime = time();
    }
    $nowtime = $nowtime ? $nowtime : time();
    $arr = array(1=>1,2=>1,3=>1,4=>2,5=>2,6=>2,7=>3,8=>3,9=>3,10=>4,11=>4,12=>4);
    //根据时间记录季度
    $q = $arr[date('n',$nowtime)];
    $y = date('Y',$nowtime);
    $back = array();
    $back[] = $y.'_'.$q;
    if($order==0){
        if($q == 1){
            $y = $y-1;
            $q = 4;
        }  else {
            $q = $q-1;
        }
    }else{
        $current_y = date('Y',time());
        if($y < $current_y){
            if($q == 4){
                $y = $y+1;
                $q = 1;
            }  else {
                $q = $q+1;
            } 
        }else{
            $current_q = $arr[date('n',time())];
            if($q == $current_q){
                $back[] = 0;
                return $back;
            }  else {
                $q = $q+1;
            } 
        }
    }
    $back[] = $y.'_'.$q;
    return $back;
}

//根据参数表后缀，后去前一个表后缀
function getNextTable($table){
    $arr = explode('_', $table);
    if($arr[1] == 1){
        $arr[0] = $arr[0]-1;
        $arr[1] = 4;
    }  else {
        $arr[1] = $arr[1]-1;
    }
    return $arr[0].'_'.$arr[1];
}
//获取字段
function get_redis_field($key) {
     $redis_field_array = [
         1   => 'poor_wallet_last_changetime',
         2   => 'needy_wallet_last_changetime',
         3   => 'comfortably_wallet_last_changetime',
         4   => 'wealth_wallet_last_changetime',//富人
         5   => 'kind_wallet_last_changetime',//德善
         6   => 'big_kind_wallet_last_changetime',
     ];
     return empty($redis_field_array[$key]) ? '' : $redis_field_array[$key];
 }
 //rsa解密解密函数

//分页取数据时，查看那个表的后缀
function getQueterTabel($table){
    if(!$table){
        return false;
    }
    $arr = explode('_', $table);
    if($arr[1]>1){
        $yue = $arr[1]-1;
        return $arr[0].'_'.$yue;
    }else{
        $nian = $arr[0]-1;
        if($nian>=2016){
            return $nian.'_4';
        }else{
            return false;
        }
    }
}
//生成不重令牌字符串
function getToken(){
    return date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}
/**
 * 随机数
 * @param type $num
 * @return type
 */
function get_rand_num($num = 6) {
    $str = '0123456789';
    $len = strlen($str);
    $r = '';
    for ($i = 0; $i < 6; $i++) {
        $rand = mt_rand(0, $len - 1);
        $r .= substr($str, $rand, 1);
    }

    return $r;
}

/**
 * http请求[post]
 * @param type $url
 * @param type $param
 * @param type $data
 * @param type $httpType
 * @param type $header
 * @return type
 */
function api_http_request($url, $data, $header = array()) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //SSL证书认证
    curl_setopt($ch, CURLOPT_URL, $url);
//        $header = array('Accept-Charset: utf-8');
//        $header[] = 'charset: utf-8';
    //$header[] = 'Content-Type: application/x-www-form-urlencoded';
    //  $header[] = 'Content-Type: multipart/form-data';
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格认证
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // post传输数据
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 显示输出结果

    $tmpInfo = curl_exec($ch);
    curl_close($ch);
    return $tmpInfo;
}
/*
 * 获取融云的token
 */
function get_user_token($user_id,$name,$header_img='') {
       
       $url = 'http://api.cn.ronghub.com/user/getToken.json';
       $arr['userId'] = $user_id;
       $arr['name'] = $name;
       $arr['portraitUri'] = $header_img;
       
       $rand = get_rand_num(10);
       $timestamp = $_SERVER['REQUEST_TIME'];
       $sign = signature(config('cloud_app_secret'),$rand,$timestamp);
       $header = array();
       $header[] = 'charset: utf-8';
       $header[] = 'App-Key:'.  config('cloud_app_key');
       $header[] = 'Nonce:'.$rand;
       $header[] = 'Timestamp:'.$timestamp;
       $header[] = 'Signature:'.$sign;
       
       $result = api_http_request($url,$arr,$header);
       if($result) {
           $data = json_decode($result);
           if($data->code == '200') {
               return $data->token;
           }
           return false;
       }
       
       return false;
       
    }
    
     /**
     * 生成签名
     * @param type $secret
     * @param type $rand
     * @param type $now
     * @return type
     */
    function signature($secret,$rand,$now) {
       
        $appSecret = $secret; // 开发者平台分配的 App Secret。
        $nonce = $rand; // 获取随机数。
        $timestamp = $now; // 获取时间戳。
        $signature = sha1($appSecret.$nonce.$timestamp);
        return $signature;
    }
    
    function returnErr($type = 0,$msg = '',$data=''){
        return array('errCode'=>$type,'msg'=>$msg,'data'=>$data);
    };