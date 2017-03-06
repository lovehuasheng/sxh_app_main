<?php
/*
name:用户上传图片接口 调用名 user.avatarphoto
desc:描述。。。。。。。。。。。。。。。。
config:file|Form-data||图片 
*/
//上传七牛云
$params['user_id'] = config('user_id');
$return = _service('avatarphoto',$params);
return  $return ;





