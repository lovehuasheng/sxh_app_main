<?php
/*
name:查看功德主钱包 调用名 user_account_info
desc:查看功德主钱包。。。。。。。。。。。。。。。。
config:
*/
$params['user_id'] = config('user_id');
$return = _service('user_account_info',$params);
return  $return ;