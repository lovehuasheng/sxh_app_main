<?php
require_once WANG_MS_PATH."model/userinfo.php";
$check_phone_code = function($data){
    $redis = RedisLib::get_instance();
    $data['phone'] = $redis->hgetUserinfoByID($data['user_id'],'phone');
    $code = cache('code'.$data['user_id'].$data['phone']);
    if(empty($code) || $code != $data['verify']){
        return returnErr(1,'验证码不正确或已过期');
    }else{
        cache('code'.$data['user_id'].$data['phone'],null);
        return returnErr(0,'验证成功');
    }
};
return $check_phone_code($arr);

