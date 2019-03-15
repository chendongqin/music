<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/2/14
 * Time: 13:15
 */
namespace base;
use think\Cache;
use think\Db;
class Userbase extends Base {
    protected $_api = array(
//        'user'=>array( 'index'),
    );

    protected function _initialize() {
        if($this->isFilter()===false){
            $user = $this->getLoginUser();
            if(empty($user)){
                $msg = array('status'=>false,'msg'=>'你没有登陆','code'=>9000,'data'=>[]);
                header('Content-type: application/json; charset=utf-8');
                echo json_encode($msg,JSON_UNESCAPED_UNICODE );
                die();
            }
            $this->assign('user',$user);
        }
    }

    protected function isFilter(){
        $request = $this->request;
        $module = strtolower($request->module());
        $controller = strtolower($request->controller());
        $action = strtolower($request->action());
        if(!isset($this->_api[$module])){
            return false;
        }
        if(!in_array($controller,$this->_api[$module])){
            return false;
        }
        if($this->_api[$module][$controller]== '*'){
            return true;
        }
        $actions = explode(',',$this->_api[$module][$controller]);
        if(in_array($action,$actions)){
            return true;
        }
        return false;
    }

    /**
     * 获取登陆用户
     * @return array|false|null|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getLoginUser(){
        $token = $this->getParam('token');
        $userId = Cache::get('user'.$token);
        if(empty($userId)){
           return null;
        }
        $user = Db::name('user')->where('user_id',$userId)->find();
        return $user;
    }

}