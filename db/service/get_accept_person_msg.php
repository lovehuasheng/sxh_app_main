<?php
require_once WANG_MS_PATH."model/publicCenter.php";
require_once WANG_MS_PATH."model/userinfo.php";
$get_pay_person_msg = function($data){
    //$redis = RedisLib::get_instance();
    $model = new publicCenter();
    $m_user = new userinfo();
    //根据时间获取表后缀
    $arr = getTable($data['create_time']);
    $table = 'sxh_user_matchhelp_'.$arr[0];
    //预定义 
    $matching = $model->getTableDesc($data['id'],'id,pid,other_id,user_id,other_user_id,status,other_money,pay_image,other_cid,create_time',$table);
    if(empty($matching) || $data['user_id'] != $matching[0]['user_id']){
        return returnErr(1,'数据错误');
    }
    $arr = array(7,8,10);
    if(in_array($matching[0]['other_cid'], $arr)){
        $user = $m_user->getCompanyInfo($matching[0]['other_user_id'],'business_center_id,company_name,legal_person as name,legal_alipay_account as alipay_account,mobile as phone,legal_bank_name as bank_name,legal_bank_account as bank_account');
        $user[0]['tel_number'] = '';
        if(isset($user[0]['business_center_id']) && $user[0]['business_center_id']>0){
            $reuser = $m_user->getCompanyInfo($user[0]['business_center_id'],'company_name,legal_person as name,mobile');
            $user[0]['referee_company_name'] = $reuser[0]['company_name'];
            $user[0]['referee_name'] = $reuser[0]['name'];
            $user[0]['referee_phone'] = $reuser[0]['mobile'];
            $user[0]['referee_tel_number'] = '';
        }else{
            $user[0]['referee_company_name'] = '';
            $user[0]['referee_name'] = '';
            $user[0]['referee_phone'] = '';
            $user[0]['referee_tel_number'] = '';
        }
    }else{
        $user = $m_user->getUserOneInfo($matching[0]['other_user_id'],'name,alipay_account,phone,weixin_account,bank_name,bank_account,tel_number,referee_id','sxh_user_info');
        $user[0]['company_name'] = '';
        $user[0]['referee_company_name'] = '';
        if(isset($user[0]['referee_id']) && $user[0]['referee_id']>0){
            $reuser = $m_user->getUserOneInfo($user[0]['referee_id'],'name,phone,tel_number','sxh_user_info');
            $user[0]['referee_name'] = $reuser[0]['name'];
            $user[0]['referee_phone'] = $reuser[0]['phone'];
            $user[0]['referee_tel_number'] = $reuser[0]['tel_number'] ? $reuser[0]['tel_number'] : '';
        }else{
            $user[0]['referee_name'] = '';
            $user[0]['referee_phone'] = '';
            $user[0]['referee_tel_number'] = '';
        }
    }
    $user[0]['images'] = $matching[0]['pay_image'] ? getQiNiuPic($matching[0]['pay_image']) : '';
    $user[0]['pid'] = $matching[0]['pid'];
    $user[0]['other_money'] = $matching[0]['other_money'];
    $user[0]['other_id'] = $matching[0]['other_id'];
    $user[0]['other_user_id'] = $matching[0]['other_user_id'];
    $user[0]['id'] = $matching[0]['id'];
    $user[0]['create_time'] = $matching[0]['create_time']; 
    //合并数据数组
    return returnErr(0,'请求成功',$user[0]);
};
return $get_pay_person_msg($arr);

