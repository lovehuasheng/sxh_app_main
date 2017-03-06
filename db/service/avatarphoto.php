<?php
require_once ROOT_PATH."extend/org/Upload.php";
require_once WANG_MS_PATH."model/userinfo.php";
$avatarphoto = function($data){
    $info = new Upload(config('upload_picture'),'Qiniu',config('qiniu'));
    $tmp = $info->upload();
    if(!$tmp) {
        return returnErr(1,$info->getError());
    }
    $m_user = new userinfo();
    $res = $m_user->updateUserInfo($data['user_id'],array('avatar'=>$tmp['file']['savename']),'sxh_user_info');
    if($res){
        return returnErr(0,'上传成功',['path'=>getQiNiuPic($tmp['file']['savename'])]);
    }else{
        return returnErr(1,'上传失败');
    }
};
return $avatarphoto($arr);

