<?php
/*
name:收款人详细信息 调用名 matchhelp.get_accept_person_msg
desc:我是收款人，获取收款人详细信息
config:id|int||匹配单号  create_time|int||单的创建时间
*/
//if(!preg_match('/^[0-9a-zA-Z]{6,16}$/', $params['username'])){
//    return returnAction(1,'用户名须为6-16位字母或数字!');
//}
//验证参数ID
if(!isset($params['id']) || intval($params['id']) === 0 || empty($params['create_time'])) {
    return returnAction(1,'参数错误!');
}
$params['user_id'] = config('user_id');
$return = _service('get_accept_person_msg',$params);
return  $return ;