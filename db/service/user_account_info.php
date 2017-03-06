<?php
require_once WANG_MS_PATH."model/userinfo.php";
$user_account_info = function($data){
    $redis = RedisLib::get_instance();
    $model = new userinfo();
    $account = $model->getUserOneInfo($data['user_id'],'order_taking,poor_wallet,needy_wallet,comfortably_wallet,kind_wallet,wealth_wallet,big_kind_wallet,activate_currency,guadan_currency,wallet_currency,manage_wallet,invented_currency','sxh_user_account');
    if(!$account) {
        return returnErr(0,'账户不存在');
    }
    $result = $account[0];
    $arr = array();
    $arr['money'][0]['money_type'] = 1;
    $arr['money'][0]['money_sum'] = $result['activate_currency'] ? $result['activate_currency'] : 0;
    $arr['money'][1]['money_type'] = 2;
    $arr['money'][1]['money_sum'] = $result['guadan_currency'] ? $result['guadan_currency'] : 0;
    $arr['money'][2]['money_type'] = 3;
    $arr['money'][2]['money_sum'] = $result['invented_currency'] ? $result['invented_currency'] : 0;
    $arr['money'][3]['money_type'] = 4;
    $arr['money'][3]['money_sum'] = $result['manage_wallet'] ? $result['manage_wallet'] : 0;
    $arr['money'][4]['money_type'] = 5;
    $arr['money'][4]['money_sum'] = $result['poor_wallet'] + $result['needy_wallet'] + $result['comfortably_wallet'] + $result['kind_wallet'] + $result['wealth_wallet'] + $result['big_kind_wallet'];
//        $arr['money'][5]['money_type'] = 6;
//        $arr['money'][5]['money_sum'] = $result['order_taking'];
    $num = $redis->hgetUserinfoByID($data['user_id'],'provide_num');
    if(empty($num) || $num<2){
        $arr['outsum'] = 0;
    }else{
//            $current_id = $redis->hgetUserinfoByID($data['user_id'],'provide_current_id');
//            $last_id = $redis->hgetUserinfoByID($data['user_id'],'provide_manage_id');
//            if($current_id != $last_id){
        //计算可提取金额
        $res_arr = $model->checkManage(intval($data['user_id']));
        if($res_arr['code'] == 0){
            $sum = $redis->hgetUserinfoByID($data['user_id'],'provide_current_money');
            $sum = $res_arr['provide_money'];//暂时不读redis
            $arr['outsum'] = intval(floor($sum/2/100)*100);
        }else{
            $arr['outsum'] = 0;
        }
    }

    $arr['outsum'] = $arr['outsum']>$result['manage_wallet'] ? intval(floor($result['manage_wallet']/100)*100) : $arr['outsum'];
    $arr['check_token'] = date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return returnErr(0,'请求成功',$arr);
};
return $user_account_info($arr);

