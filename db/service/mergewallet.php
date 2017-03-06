<?php
include  "../db/model/accepthelp.php";
/* 
 * 合并钱包
 */
if(!is_numeric($arr['user_id']) || $arr['user_id'] < 0){
    return returnErr(1,'请先登录');
}
if(empty($arr['sed_pwd'])){
    return returnErr(1,'二级密码不能为空');
}

$mergewallet = function($data){
    $accept     = new accepthelp();
    $user_data  = $accept->getUserDetail($data['user_id']);
    if(empty($user_data)){
        return returnErr(1,'用户信息有误');
    }
    if($user_data['status'] != 1){
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
    if($user_data['sum'] == $user_data['meger'] ){
        return returnErr(0,'本次可直接接受资助');
    }
    //$gap = $accept->getUserGap(intval($data['user_id']));
    //return $gap;
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
    $p['arr']      = $arr;
    $p['username'] = $user_data['username'];
    $p['sum']      = $user_data['sum'];
    $p['user_id']  = $data['user_id'];
    $return = $accept->mergewalletDo($p);
    return $return;
};
return $mergewallet($arr);
