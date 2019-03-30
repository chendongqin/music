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
use think\Exception;

class Userbase extends Base
{
    protected $_api = array();

    protected function _initialize()
    {
        if ($this->isFilter() === false) {
            $user = $this->getLoginUser();
            if (empty($user)) {
                $msg = array('status' => false, 'msg' => '你没有登陆', 'code' => 9000, 'data' => []);
                header('Content-type: application/json; charset=utf-8');
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                die();
            }
            $this->assign('user', $user);
        }
    }

    protected function isFilter()
    {
        $request = $this->request;
        $module = strtolower($request->module());
        $controller = strtolower($request->controller());
        $action = strtolower($request->action());
        if (!isset($this->_api[$module])) {
            return false;
        }
        if (!in_array($controller, $this->_api[$module])) {
            return false;
        }
        if ($this->_api[$module][$controller] == '*') {
            return true;
        }
        $actions = explode(',', $this->_api[$module][$controller]);
        if (in_array($action, $actions)) {
            return true;
        }
        return false;
    }

    //用户登出清除缓存
    public function logoutUser($userId)
    {
        $code = Cache::get('userCode' . $userId);
        $token = $this->getParam('token');
        if (!$token) {
            $token = sha1($code . $userId);
        }
        Cache::rm('userCode' . $userId);
        Cache::rm('user' . $token);
        return true;
    }


    public function addLove($user,$song)
    {
        if ($loves = Db::name('loves')->where(['user_id' => $user['user_id'], 'song_id' => $song['song_id']])->find()) {
            return true;
        }
        $data = array(
            'user_id' => $user['user_id'], 'song_id' => $song['song_id'], 'create_at' => time()
        );
        Db::startTrans();
        $res = Db::name('loves')->insert($data);
        if (!$res) {
            Db::rollback();
            return false;
        }
        $model = Db::name('loves_log');
        if ($song['is_old']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_old', $song['is_old'])) {
                Db::rollback();
                return false;
            }
        }
        if ($song['is_popular']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_popular', $song['is_popular'])) {
                Db::rollback();
                return false;
            }
        }
        if ($song['is_dj']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_dj', $song['is_dj'])) {
                Db::rollback();
                return false;
            }
        }
        if ($song['is_classical']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_classical', $song['is_classical'])) {
                Db::rollback();
                return false;
            }
        }
        if ($song['is_flok']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_flok', $song['is_flok'])) {
                Db::rollback();
                return false;
            }
        }
        if ($song['is_rap']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_rap', $song['is_rap'])) {
                Db::rollback();
                return false;
            }
        }
        Db::commit();
        return true;
    }


}