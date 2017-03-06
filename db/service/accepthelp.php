<?php
include  "../db/model/accepthelp.php";
/*
 * 接受资助
 */
if(empty($arr['user_id']) || $arr['user_id']<1 || !is_numeric($arr['user_id'])){
   return  returnErr(1,'请先登录');
}
if(!is_numeric($arr['cid']) || !in_array($arr['cid'],[1,2,3,4,5])){
    return  returnErr(1,'请选择正确的社区！');
}
if(intval($arr['money'])<500){
    return  returnErr(1,'接受资助金额必须大于500');
}
if(intval($arr['money'])%100){
    return  returnErr(1,'接受资助金额必须必须是规定金额的倍数');
}
if(empty($arr['sed_pwd'])){
   return  returnErr(1,'密码不能为空');
}

$accepthelp = function($data){
    $accept     = new accepthelp();
    $user_data  = $accept->getUserDetail($data['user_id']);
    if(empty($user_data)){
        return returnErr(1,'用户信息有误');
    }
    if($user_data['errCode'] != 1){
        return returnErr(1,'请先激活');
    }
    if($user_data['verify'] != 2){
        return returnErr(1,'请先通过审核');
    }
    if($user_data['security']){
        $pwd       = set_secondary_password(trim($data['sed_pwd']));/*二级密码*/
        $pwd_login = set_password(trim($data['sed_pwd']),$user_data['security']);/*登录密码*/
    }else{
        $pwd       = set_old_password(trim($data['sed_pwd']));/*二级密码*/
        $pwd_login = set_old_password(trim($data['sed_pwd']));/*登录密码*/
    }
    if($pwd != $user_data['secondary_password']){
        return returnErr(1,'二级密码错误');
    }
    if($pwd == $pwd_login){
        return returnErr(1,'二级密码与登陆密码不能一致!');
    }
    if($user_data['id'] > 0){
        return returnErr(1,'请先解除黑名单!');
    }
    if($user_data['sum'] > $user_data['meger'] ){
        return returnErr(1,'请先合并钱包');
    }
    /*用户是否有过挂单*/
    $provide = $accept->getUserProvide($data['user_id']);
    if($provide == 0){
        return returnErr(1,'至少完成一笔提供资助，才能接受资助');
    }
    /*新需求，挂单未完成的不允许接受资助*/
    $provide_not_finish = $accept->getUserProvideFinish($data['user_id']);
    if($provide_not_finish == 0){
        return returnErr(1,'您有未完成的提供资助');
    }
    /*是否有未完成的接受资助*/
    $accept_res = $accept->getUserAccept(intval($data['user_id']));
    if($accept_res == 0){
        return returnErr(1,'有未完成的接受资助');
    }
    if($user_data['mw']>500){
        $gap = $accept->getUserGap(intval($data['user_id']));
        if($gap == 1){
            return errReturn('请先提取管理奖',1 );
        }
    }
    /*合并钱包*/
    $arr[1]['k'] = intval($user_data['pw']);$arr[1]['sort'] = 6;$arr[1]['c'] = 1;$arr[1]['field'] = 'poor_wallet';$arr[1]['cname']='特困社区';$arr[1]['type']=7;
    $arr[2]['k'] = intval($user_data['nw']);$arr[2]['sort'] = 5;$arr[2]['c'] = 2;$arr[2]['field'] = 'needy_wallet';$arr[2]['cname']='贫穷社区';$arr[2]['type']=8;
    $arr[3]['k'] = intval($user_data['cw']);$arr[3]['sort'] = 4;$arr[3]['c'] = 3;$arr[3]['field'] = 'comfortably_wallet';$arr[3]['cname']='小康社区';$arr[3]['type']=9;
    $arr[4]['k'] = intval($user_data['ww']);$arr[4]['sort'] = 3;$arr[4]['c'] = 4;$arr[4]['field'] = 'wealth_wallet';$arr[4]['cname']='富人社区';$arr[4]['type']=11;
    $arr[5]['k'] = intval($user_data['kw']);$arr[5]['sort'] = 2;$arr[5]['c'] = 5;$arr[5]['field'] = 'kind_wallet';$arr[5]['cname']='德善社区';$arr[5]['type']=10;
    $arr[6]['k'] = intval($user_data['bkw']);$arr[6]['sort'] = 1;$arr[6]['c'] = 6;$arr[6]['field'] = 'big_kind_wallet';$arr[6]['cname']='大德社区';$arr[6]['type']=15;
    rsort($arr);
    $data['arr']      = $arr[0];
    $data['username'] = $user_data['username'];
    $data['name']     = $user_data['name'];
    $return = $accept->doSaveAccept($data);
    return $return;
};
return $accepthelp($arr);