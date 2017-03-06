<?php
namespace app\relation\controller;
use think\Request;
use think\Response;
use think\Session;
class U{
    protected $cur_url;
    private function __init(){
		
	}	
	public function _empty($act){
		$act=strtolower($act);
		$dir=dirname(__FILE__)."/u/".$act.".php";
		session("uid","1");
		if(!file_exists($dir)){
			abort(404, '你请求的页面不存在', []);
		}else{
			$flag=$this->__init();			
			$r=include($dir);
			return view($r[0],$r[1]);                        
		}		
	}
}