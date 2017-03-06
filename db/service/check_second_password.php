<?php
require_once WANG_MS_PATH."model/userinfo.php";
$check_second_password = function($data){
    //根据post数据，获取用户信息
    $m_user = new userinfo();
    $user = $m_user->getUserOneInfo($data['user_id'],'id,security,secondary_password','sxh_user');
    if(!$user){
        return returnErr(1,'用户ID不存在');
    }
    if($user[0]['security']){
        $pwd = set_secondary_password(htmlspecialchars(urldecode($data['password'])));
    }else{
        $pwd = set_old_password(htmlspecialchars(urldecode($data['password'])));
    }
    if($user[0]['secondary_password'] != $pwd) {
        return returnErr(1,'二级密码错误');
    }else{
        return returnErr(0,'验证成功');
    }
};
return $check_second_password($arr);