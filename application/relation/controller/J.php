<?php
namespace app\relation\controller;
use think\Request;
use think\Response;
use think\Session;
class J {
	public function _empty($act){
		$act=strtolower($act);
		$dir=dirname(__FILE__)."/j/".$act.".php";
		/*
			假定当前用户 id 
			系统集成的时候，必须从用户已经登录session中获取
		*/
		if(!file_exists($dir)){
			abort(404, '你请求的页面不存在', []);
		}else{
			$this->__init();
			session("uid","174");
			session("plevel","7");
			$r=include($dir);
			if(!empty($r->__system__)){
				$re=$r;
			}else{
				$re=new \stdClass();
				$re->errCode=0;
				$re->result=$r;
				$re->errMsg="";
			}
			header("Content-type: application/json");
			return json($re,200);
		}
	}
	private function __init(){
	}
}