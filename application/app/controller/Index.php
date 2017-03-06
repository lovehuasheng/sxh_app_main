<?php
namespace app\app\controller;

class Index
{
    public function index()
    {
		  /*
		  app 接口处理方法
		  1 公共参数判断 
		  2 调用相应版本的业务方法
		  */
		
		$_params=input('post.');
		$r=$this->check($_params); //系统校验
		if($r!=0){
			return $this->getReturnData($r); 
		}	   
		$r=$this->parseUser($_params);
                if($r[0]<=0){
                    $arr = array('user.register','user.login','user.check_username','user.get_phone_code','user.mod_user_password');
                    if(!in_array($_params['a'], $arr)){
                        return $this->getReturnData(14001,'','登录超时，请重新登录');
                    }
                }
                config("user_id",intval($r[0])); //解析出向下业务唯一需要身份,用户id
		$return=$this->execAction($_params);		
		return $this->getReturnData(0,$return);
    }
	public function test_interface(){
		return view('test_interface');
	}
	public function test_get_action(){ //获取当前版本的所有操作
		$v=config('copyright.current');
		$_path=config('service.action').$v.DS;
		$r=[];
		$dir=$_path;
		$this->scanAction($dir,$r);
		header("Content-type: application/json");
		return json($r,200);
	}
	public function get_action_info(){
		$v=config('copyright.current');
		$_path=config('service.action').$v.DS;
		$a=$_path.str_replace(".",DS,input('get.a')) . ".php";
		if(!file_exists($a)){
			return json(['errCode'=>1]);
		}
		return json(array_merge($this->query_action($a),array('a'=>input('get.a'))),200);
	}
	public function get_p(){
		$_s=$this->getSign(input('post.'));
		return json(['errCode'=>0,'sign'=>$_s]);
	}
	private function check($_params){ //通过返回0 ，其他都是错误 
		//判断时间合理
		if(empty($_params['t'])) return 10000;
		if(!preg_match("/^\d+$/",$_params['t'])) return 10001;
		$_t=intval($_params['t']);
		if($_t>time()) return 10002;
		if($_t+10<time()) return 10003;
		//判断 系统身份标识
		if($_params['k']!="app_poly") return 10004;
		//判断 版本是否允许使用 
		if(empty($_params['v'])) return 11000; //没有版本号
		if(!preg_match("/^\d+$/",$_params['v'])) return 11001; //版本号不正规
		$_v=intval($_params['v']);
		if($_v<config('copyright.before')) return 11002;//版本号过期
		if($_v>config('copyright.current')) return 11003;//版本号不存在
		//判断 请求接口是否合理 
		$_a=$_params['a'];
		if(empty($_a)) return 12001;//没有带请求接口
		if(!$this->getAction($_v,$_a)) return 120002 ;//没有请求接口对应的业务入口文件
		if(empty($_params['f'])) return 13000;//没有秘钥
		if(!preg_match("/^[a-zA-z0-9]{6,32}$/",$_params['f'])) return 13001;//秘钥不合理
		if(!$this->sign($_params)) return 13002;//非法请求,密文不正确
		return 0;
	}
	private function  getAction($copyright,$action){ //判断指定版本号的操作是否存在
		$_path=config('service.action').$copyright.DS.str_replace(".",DS,$action).".php";
		if(!file_exists($_path)) return false;
		return true;
	}
	private function getSign($params){//加密
		$f=array();
		foreach($params as $k=>$v){
//			$k=strtolower($k);
//			$v=strtolower($v);
			if($k=='p') continue;
			if(preg_match("/^\_/",$k))continue;
			$f[]=urlencode($k)."=".urlencode($v);
		}
		sort($f);
		$_f=join("&",$f);
                $_f = strtolower($_f);
		$_s= md5(config("app.crypt").$_f.$params['f']);
		return $_s;
	}
	private function sign($params){//加密验证
		$_s=$this->getSign($params);
		if($_s==$params['p']) return true;
		return false;
	}
	private function execAction($params){ //执行具体业务
		$c=$params['v']; //版本号
		$a=$params['a'];
		$_a=explode(".",$a);
		$startPath=config('service.action').$c.DS;
		if(file_exists($startPath."__init.php")){
			$r=include($startPath."__init.php");
			if($r['errCode']!=0){
				return $r;
			}
		}
		$_path=$startPath;
		for($i=0;$i<count($_a)-1;$i++){
			$_path.=$_a[$i].DS;
			if(file_exists($_path."__init.php")){
				$r=include($_path."__init.php");
				if($r['errCode']!=0){
					return $r;
				}
			}
		}
                
		return include($_path.$_a[count($_a)-1].".php");
	}
	private function parseUser($params){
		//解析用户标志，得出用户id，和有效时间，以及返回错误码（0 表示正确）
		 //UID ."_". md5(__PRE_KEY__ .UID . LIMIT_TIME) ."_".LIMIT_TIME;
		 if(empty($params['s'])) return [0,0,1];
		 $_f=$params['s'];		 
		 $f=explode("_",$_f);
		 if(count($f)!=3){
			return [0,0,1];
		 }
		 $p=md5(config("app.token").$f[0].$f[2]);
		 if($p!=$f[1]) return [0,0,2];
		 if(!preg_match("/^\d+$/",$f[2])) return [0,0,3];
		 if(intval($f[2]<time())) return [0,0,4];
		 if(intval($f[2])>time()+20*60) return [0,0,5];
		 return [$f[0],$f[2],0];
	}
	private function getReturnData($errCode,$data=[],$msg="",$report="",$tk=""){
		$r=new \stdClass();
		$r->errCode=$errCode;
                if(empty($data)){
                    $data = ['errCode'=>1,'msg'=>'','data'=>''];
                }
		$r->data=$data;
		$r->msg= ($errCode!=0) ? '请求超时':$msg;
		$r->token=$this->getUseCryptString();
		$_t=microtime(true)-THINK_START_TIME ;
		$r->report="执行时间:${_t}秒";
		header("Content-type: application/json");
		return json($r,200);
	}
	private function getUseCryptString(){
		$t=time()+20*60;//有效时间20分钟
		return config("user_id")."_".md5(config("app.token").config("user_id").$t)."_".$t;
	}
	private function scanAction($dir, & $r,$pre=[]){
		$list = scandir($dir); 
		foreach($list as $file){
			$file_location=$dir. DS .$file;//生成路径
			if( $file=="." || $file=="..") continue;
			if(!is_dir($file_location)){
				if(preg_match("/^\_/",$file))continue;
				if(!preg_match("/\.php$/",$file))continue;
				$item=new \stdClass();
				$item->name=substr($file,0,-4);
				$item->label=join(".",array_merge($pre,array($item->name)));
				$item->html="<i class='icon icon-cog'> </i><span style='cursor:pointer' flag='action_node' title='点击测试接口 ".$item->label."' a='".$item->label."'>". $item->label ." </span>";
				$item->flag=1;
				$r[]=$item;
			}else{
				$item=new \stdClass();
				$item->name=$file;
				$item->html=$file;
				$item->flag=0;
				$item->children=[];
				$this->scanAction($file_location,$item->children,array_merge($pre,array($file)));
				$r[]=$item;
			}
		}		
	}
	private function query_action($file){
		if(!file_exists($file)) return false;
		$data=file($file);
		$title=$data[2];
		$desc=$data[3];
		$cfg=$data[4];
		return ['title'=>$title,'desc'=>$desc,'cfg'=>$cfg];
	}
}
