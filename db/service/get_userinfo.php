<?php
require_once WANG_MS_PATH."model/userinfo.php";
$get_userinfo = function($data){

    $model = new userinfo();
    $field = 'phone,weixin_account,email,city,name,card_id,address,province,town,area,alipay_account,bank_name,bank_account,'
            . 'image_a,image_b,image_c,bank_address,verify';
    $result_s = $model->getUserOneInfo($data['user_id'],$field,'sxh_user_info');
    $result = $result_s[0];
    if(strpos($result['bank_name'], '-')){
        $temp = explode('-', $result['bank_name']);
        $result['bank_name'] = $temp[0];
        $result['bank_address'] = $temp[1];
    }
    $result['province'] = $result['province'] ? $result['province'] : '';
    $result['town'] = $result['town'] ? $result['town'] : '';
    $result['area'] = $result['area'] ? $result['area'] : '';
    $result['address'] = str_replace($result['province'].$result['town'].$result['area'],'',$result['address']);
    $result['image_a'] = $result['image_a'] ? getQiNiuPic($result['image_a']) : '';
    $result['image_b'] = $result['image_b'] ? getQiNiuPic($result['image_b']) : '';
    $result['image_c'] = $result['image_c'] ? getQiNiuPic($result['image_c']) : '';
    $result['flag'] = true;
    if($result['verify'] != 2){
        $result['flag'] = false;
    }
    return returnErr(0,'取出数据成功',$result);
};
return $get_userinfo($arr);

