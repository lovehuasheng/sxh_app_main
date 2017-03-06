<?php
require_once WANG_MS_PATH."model/userinfo.php";
require_once ROOT_PATH."extend/org/Upload.php";
$perfect_userinfo = function($data){
    $redis = RedisLib::get_instance();
    
    //微信号如果不为空则验证是否含有特殊字符和其唯一性
    $m_info = new userinfo();
    $user_id = intval($data['user_id']);
    $info = $m_info->getUserOneInfo($user_id,'weixin_account,alipay_account,card_id,bank_account,verify','sxh_user_info');
    $res_info = $info[0];
    //审核通过的用户不能再次修改资料
    if($res_info['verify'] == 2){
        return returnErr(1,'审核通过的用户不能修改资料');
    }
    if(!empty($data['weixin_account'])){
        $data['weixin_account'] =preg_replace("/\s/","",$data['weixin_account']);
        if($data['weixin_account'] != $res_info['weixin_account']){
            $weixin_account_result  = $redis->sismemberFieldValue('sxh_user_info:weixin_account' , $data['weixin_account']);
            if($weixin_account_result){
                return returnErr(1,'微信账号已被其他人使用');
            }
        }
    }
    //支付宝号如果不为空则验证是否含有特殊字符和其唯一性
    if(!empty($data['alipay_account'])){
        $data['alipay_account'] =preg_replace("/\s/","",$data['alipay_account']);
        if($data['alipay_account'] != $res_info['alipay_account']){
            $alipay_account_result  = $redis->sismemberFieldValue('sxh_user_info:alipay_account' , $data['alipay_account']);
            if($alipay_account_result){
                return returnErr(1,'支付宝账号已被其他人使用');
            }
        }
    }
    $data['phone'] =preg_replace("/\s/","",$data['phone']);
    $data['card_id'] =preg_replace("/\s/","",$data['card_id']);
    $data['bank_account'] =preg_replace("/\s/","",$data['bank_account']);

    //防止用户资料未审核通过期间修改资料，如果存在则要相应的修改redis
    if($data['card_id'] != $res_info['card_id']){
        $rec = $redis->sismemberFieldValue('sxh_user_info:card_id' , $data['card_id']);
        if($rec){
            return returnErr(1,'身份证号已被其他人使用');
        }
    }
    if($data['bank_account'] != $res_info['bank_account']){
        $reb = $redis->sismemberFieldValue('sxh_user_info:bank_account' , $data['bank_account']);
        if($reb){
            return returnErr(1,'银行账号已被其他人使用');
        }
    }
    $img_image = $_FILES;
    $img = array();
    if((isset($img_image['image_a']) && !empty($img_image['image_a'])) || (isset($img_image['image_b']) && !empty($img_image['image_b'])) || (isset($img_image['image_c']) && !empty($img_image['image_c']))){
        //上传七牛云
        $info = new Upload(config('upload_picture'),'Qiniu',config('qiniu'));
        $tmp = $info->upload();
        if(!$tmp) {
            return returnErr(1,$info->getError());
        }
        if(isset($img_image['image_a']) && !empty($img_image['image_a'])){
            $img['image_a'] = $tmp['image_a']['savename'];
        }
        if(isset($img_image['image_b']) && !empty($img_image['image_b'])){
            $img['image_b'] = $tmp['image_b']['savename'];
        }
        if(isset($img_image['image_c']) && !empty($img_image['image_c'])){
            $img['image_c'] = $tmp['image_c']['savename'];
        }
    }
    //处理数据
    $sdata = array();
    $sdata['phone'] = $data['phone'];
    $sdata['weixin_account'] = $data['weixin_account'] ? $data['weixin_account'] : '';
    $sdata['email'] = $data['email'];
    $sdata['city'] = $data['city'];
    $sdata['name'] = $data['name'];
    $sdata['card_id'] = $data['card_id'];
    $sdata['address'] = $data['address'];
    $sdata['alipay_account'] = $data['alipay_account'] ? $data['alipay_account'] : '';
    $sdata['bank_name'] = $data['bank_name'];
    $sdata['bank_address'] = $data['bank_address'];
    $sdata['bank_account'] = $data['bank_account'];
    $sdata['province'] = $data['province'];
    $sdata['town'] = $data['town'];
    $sdata['area'] = $data['area'];
    if(isset($img['image_a']) && !empty($img['image_a'])){
        $sdata['image_a'] = $img['image_a'];
    }
    if(isset($img['image_b']) && !empty($img['image_b'])){
        $sdata['image_b'] = $img['image_b'];
    }
    if(isset($img['image_c']) && !empty($img['image_c'])){
        $sdata['image_c'] = $img['image_c'];
    }
    $res = $m_info->updateUserInfo($data['user_id'],$sdata,'sxh_user_info');
    if($res){
        //防止用户资料未审核通过期间修改资料，如果存在则要相应的修改redis
        if($data['card_id'] != $res_info['card_id']){
            if(!empty($res_info['card_id'])){
                $redis->sremUserInfoField('card_id',$res_info['card_id']);
            }
        }
        if($data['bank_account'] != $res_info['bank_account']){
            if(!empty($res_info['bank_account'])){
                $redis->sremUserInfoField('bank_account',$res_info['bank_account']);
            }
        }
        if(!empty($data['weixin_account'])){
            if($data['weixin_account'] != $res_info['weixin_account']){
                if(!empty($res_info['weixin_account'])){
                    $redis->sremUserInfoField('weixin_account',$res_info['weixin_account']);
                }
                $redis->saddField(  'sxh_user_info:weixin_account' , $sdata['weixin_account'] );
            }
        }else{
            //第一次不为空，而第二次为空的情况
            if(!empty($res_info['weixin_account'])){
                $redis->sremUserInfoField('weixin_account',$res_info['weixin_account']);
            }
        }
        //支付宝号如果不为空则验证是否含有特殊字符和其唯一性
        if(!empty($data['alipay_account'])){
            if($data['alipay_account'] != $res_info['alipay_account']){
                if(!empty($res_info['alipay_account'])){
                    $redis->sremUserInfoField('alipay_account',$res_info['alipay_account']);
                }
                $redis->saddField(  'sxh_user_info:alipay_account' , $sdata['alipay_account'] );
            }
        }else{
            //第一次不为空，而第二次为空的情况
            if(!empty($res_info['alipay_account'])){
                $redis->sremUserInfoField('alipay_account',$res_info['alipay_account']);
            }
        }
        
        return returnErr(0,'保存成功');
    }else{
        return returnErr(0,'保存成功');
    }
};
return $perfect_userinfo($arr);

