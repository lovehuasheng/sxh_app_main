<?php
/*
name:根据用户账号查询真实姓名接口 调用名 user.find_user_name
desc:根据用户账号查询真实姓名接口
config:username|text||用户名
*/
//if(!preg_match('/^[0-9a-zA-Z]{6,16}$/', $params['username'])){
//    return returnAction(1,'用户名须为6-16位字母或数字!');
//}
$return = _service('find_user_name',$params);
return  $return ;

