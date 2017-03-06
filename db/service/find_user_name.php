<?php
require_once WANG_MS_PATH."model/userinfo.php";
$find_user_name = function($data){
    $redis = RedisLib::get_instance();
    $data['username'] = strtolower($data['username']);
    $user_id = $redis->getUserId($data['username']);
    if($user_id){
        $model = new userinfo();
        $res = $model->getUserInfo($user_id,array('user_id'=>$user_id),'name','sxh_user_info');
        if($res){
            return returnErr( 0,'请求成功',array('name'=>$res[0]['name']));
        }else{
            return returnErr(1,'用户名不存在!');
        }
    }else{
        return returnErr(1,'用户名不存在!');
    }
};
return $find_user_name($arr);

