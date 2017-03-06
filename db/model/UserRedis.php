<?php
/* 公共类（不用先登录）
 */
class UserRedis extends \Redis{
    static $_instance;
    public $redis;
    
    
//    public function __construct() {
//       parent::redis = \org\RedisLib::get_instance();
//    }
//    
//    public static function instance(){
//        if(isset(self::$_instance) && self::$_instance instanceof self)
//        {
//                return self::$_instance;
//        }
//        self::$_instance=new self();
//        return self::$_instance;
//    }
    //根据ID获取用户名
    public function getUsernameByID($user_id){
        return parent::get('sxh_user:id:'.$user_id.':username');
    }
    //根据ID存用户名
    public function setUsernameByID($user_id,$username){
        return parent::set('sxh_user:id:'.$user_id.':username',$username);
    }
    /** 根据用户名获取用户 id (username:admin2:id)
     */
    public function getUserId($username) {
        $user_id = parent::get('sxh_user:username:'.$username.':id');
        if(!$user_id){
            include_once  WANG_MS_PATH."model/userinfo.php";
            $m_user = new userinfo();
            $user_id = $m_user->setUserRedis($username);
        }
        return $user_id;
    }
    
    
    /** set 设置 id（用户名+id绑定）（sxh_user:username:admin2:id 2）
     */
    public function setUserId($username , $id) {
        return parent::set('sxh_user:username:'.$username.':id' , $id);
        
    }
    
    
    /** set 设置 id（手机号+id绑定）（sxh_user_info:phone:18676606234:id 2）
     */
    public function setUserPhoneId($phone , $id) {
        return parent::set('sxh_user_info:phone:'.$phone.':id' , $id);
    }
    
    
    /** 根据手机号获取用户id (sxh_user_info:phone:18676606234:id)
     */
    public function getUserIdByPhone($phone) {
        return parent::get('sxh_user_info:phone:'.$phone.':id');
    }
    
    
    
    
    /** 根据用户名，判断用户是否存在（存在1，不存在返回0）
     */
    public function existsUserId($username) {
        return parent::exists('sxh_user:username:'.$username.':id');
    }
    
    
    /** sadd 添加集合一条
     */
    public function saddField($field , $value) {
        return parent::sadd($field , $value);
    }
    
    /** smembers 获取集合列表
     */
    public function smembersField($field) {
        return parent::smembers($field);
    }
    
    /** sismember 判断集合 值 是否存在
     */
    public function sismemberFieldValue($field , $value) {
        return parent::sismember($field , $value);
    }
    
    /** 删除 手机号集合
     * username:admin2:id
     */
    public function sremPhoneField($phone) {
        return parent::srem('sxh_user_info:phone' , $phone);
    }
    
    /** 删除 手机号集合
     * username:admin2:id
     */
    public function  sremUserInfoField($field , $val) {
        return parent::srem('sxh_user_info:'.$field , $val);
    }
    
    //删除手机 键
    public function delPhoneField($phone , $user_id) {
        return parent::del('sxh_user_info:phone:'.$phone.':id' , $user_id);
    }
    
    //根据ID存用户的相关信息
    public function hsetUserinfoByID($id,$field,$value){
        return parent::hSet('sxh_userinfo:id:'.$id,$field,$value);
    }
    //根据用户ID和字段获取用户的缓存信息
    public function hgetUserinfoByID($id,$field){
        return parent::hGet('sxh_userinfo:id:'.$id,$field);
    }
    //为哈希表 key 中的指定字段的整数值加上增量 increment 
    public function hIncrByUserinfoByID($id,$field,$increment){
        return parent::hIncrBy('sxh_userinfo:id:'.$id,$field,$increment);
    }
    /**
     * 
     * @param type $table 值为provide或者accepthelp
     * @param type $user_id 用户ID
     * @param type $type 1为未匹配 2为已匹配 3为已完成
     * @param type $data json_encode数据，以分页的形式保存
     * @return 
     */
    public function rPushDataList($table,$user_id,$type,$data){
        return parent::rPush('sxh_user_'.$table.'_list:user_id:'.$user_id.':type:'.$type,$data,60);
    }
    /**
     * @param type $table 值为provide或者accepthelp
     * @param type $user_id
     * @param type $type
     * @param type $index 索引取出数据
     * @return type
     */
    public function lindexDataList($table,$user_id,$type,$index){
        return parent::lindex('sxh_user_'.$table.'_list:user_id:'.$user_id.':type:'.$type,$index);
    }
    
    public function delDataList($table,$user_id,$type){
        parent::del('sxh_user_'.$table.'_list:type:web:user_id:'.$user_id.':type:'.$type);
        return parent::del('sxh_user_'.$table.'_list:user_id:'.$user_id.':type:'.$type);
    }
    /**
     * 根据用户ID和单ID获取已匹配数据
     * @param type $table 值为provide 和 accepthelp
     * @param type $user_id
     * @param type $id
     * @return type
     */
    public function getMatchDetail($table,$user_id,$id){
        return parent::get('sxh_user_'.$table.'_detail:user_id:'.$user_id.':id:'.$id);
    }
    /**
     * 根据用户ID和单ID设置已匹配数据
     * @param type $table 值为provide 和 accepthelp
     * @param type $user_id
     * @param type $id
     * @return type
     */
    public function setMatchDetail($table,$user_id,$id,$data){
        $exr = config('redis_expiration_time');
        return parent::set('sxh_user_'.$table.'_detail:user_id:'.$user_id.':id:'.$id,$data,60);
    }
    public function delMatchDetail($table,$user_id,$id){
        parent::set('sxh_user_'.$table.'_detail:type:web:user_id:'.$user_id.':id:'.$id,null);
        return parent::set('sxh_user_'.$table.'_detail:user_id:'.$user_id.':id:'.$id,null);
    }
    

}
