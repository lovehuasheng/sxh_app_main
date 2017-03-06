<?php
require_once WANG_MS_PATH."model/userinfo.php";
$check_username = function($data){
    $redis = RedisLib::get_instance();
    //查帐户是否已被注册
    $data['username'] = strtolower($data['username']);
    if($redis->sismemberFieldValue('sxh_user:username' , $data['username'])) {
        return returnErr(1,'帐户已经被注册');
    }else{
        $m_user = new userinfo();
        $relation_result = $m_user->getRelationInfo(['username'=>$data['username']] , 'user_id');
        if(!empty($relation_result)){
            return returnErr(1,'帐户已经被注册!');
        }
        return returnErr(0,'用户名可用!');
    }
};
return $check_username($arr);

