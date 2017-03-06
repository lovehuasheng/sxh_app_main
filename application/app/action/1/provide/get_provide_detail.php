<?php
/*
name:获取提供资助的匹配详情 调用名 provide.get_provide_detail
desc:描述。。。。。。。。。。。。。。。。
config:id|int||提供资助单号  create_time|int||单的创建时间
*/
//if(!preg_match('/^[0-9a-zA-Z]{6,16}$/', $params['username'])){
//    return returnAction(1,'用户名须为6-16位字母或数字!');
//}
//验证参数ID
if(!isset($params['id']) || intval($params['id']) === 0 || empty($params['create_time'])) {
    return returnAction(1,'参数错误!');
}
$params['user_id'] = config('user_id');
$return = _service('get_provide_detail',$params);
return  $return ;