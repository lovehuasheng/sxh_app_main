<?php
    include  "../db/model/user.php";
/*
 * 提供资助消耗的善心币
 */
    /*基础数据验证*/
    
    if(!is_numeric($arr['user_id']) || $arr['user_id'] <1){
        return returnErr(1,'请先登录');
    }
    if(!is_numeric($arr['cid']) || !in_array($arr['cid'],[1,2,3,4,5])){
        return returnErr(1,'请选择正确的社区！');
    }
$provide_sonsume = function($data){
    $model = new user();
    $info = $model->getUserDetail(intval($data['user_id']),intval($data['cid']));
    if(empty($info)){
        return returnErr(1,'用户不存在');
    }
    if($info['status'] != 1){
        return returnErr(1,'账户未激活');
    }
    if($info['verify'] != 2){
        return returnErr(1,'账户未审核');
    }
    if($info['bid'] != ''){
        return returnErr(1,'账户处于黑名单');
    }
    if($info['special'] == 1){
        return returnErr(1,'管理员不得参与挂单');
    }
    if($info['is_poor']==0 && $info['cid']==1){
        return returnErr(1,'您不是特困会员，不能在特困区挂单');
    }
    $a = $model->getGccount(intval($data['user_id']));
    $guadan['guadan_consume']  = $info['nc'];
    $guadan['guadan_currency'] = $info['gc'];
    if($a == 1){  
        $guadan['guadan_consume'] = 2*$info['nc'];
    }
    return returnErr(0,'',$guadan);
};

/*执行调用*/
return $provide_sonsume($arr);