<?php
/*
name:完善资料接口 调用名 perfect_userinfo
desc:完善资料接口。。。。。。。。。。。。。。。。
config:phone|string||手机号  weixin_account|string||微信号   email|string||电子邮箱  city|string||居住城市  name|string||身份姓名  card_id|string||身份证号  address|string||收货地址  alipay_account|string||支付宝  bank_name|string||银行名称  bank_address|string||所属支行  bank_account|string||银行卡号  province|string||所在省份  town|string||所在市  area|string||所在区  image_a|string||身份证正面  image_b|string||身份证反面  image_c|string||手持身份证
*/
if(empty($params['city']) || empty($params['address']) || empty($params['province']) || empty($params['town'])){
    return returnAction(1,'参数不完整');
}
if(!preg_match('/^[\x{4e00}-\x{9fa5}\w-\.\s]{1,250}$/u',$params['city'].$params['address'].$params['province'].$params['town'].$params['area'])){
    return returnAction(1,'地址信息只能包含1-250位中文、数字、字母、“-”、“.”');
}
if(!preg_match('/^(\d{10,30})$/',$params['bank_account'])){
    return returnAction(1,'银行账号格式不正确');
}
if(!preg_match('/^[^&^=^%^$^@^\)^\)^\~^\+^\[^\]^\}^\{^\<^\>^\*^\d]{2,80}$/i',$params['name'])){
    return returnAction(1,'姓名长度需在1-40个中文或字母字符之间');
}
if(!preg_match('/^[\x{4e00}-\x{9fa5}\w-]{1,50}$/u',$params['bank_name'].$params['bank_address'])){
    return returnAction(1,'开户银行与所在支行的字符总长度为1-49位中文、数字或字母');
}
if(!preg_match('/^(\w{10,18})$/',$params['card_id'])){
    return returnAction(1,'身份证格式不正确');
}
if(!preg_match('/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i',$params['email'])){
    return returnAction(1,'邮箱格式不正确');
}

if(!preg_match('/^1[34578]\d{9}$/', $params['phone'])){
    return returnAction(1,'手机号码格式有误');
}
if(!empty($params['alipay_account'])){
    if(preg_match('/\!|\%|\&|\(|\)|\<|\>|\;|\'|\"/',$params['alipay_account'])){
        return returnAction(1,'支付宝号不能含有特殊字符');
    }
}
if(!empty($params['weixin_account'])){
    if(preg_match('/\!|\%|\&|\(|\)|\<|\>|\;|\'|\"/',$params['weixin_account'])){
        return returnAction(1,'微信号不能含有特殊字符');
    }
}
$params['user_id'] = config('user_id');
$return = _service('perfect_userinfo',$params);
return  $return ;