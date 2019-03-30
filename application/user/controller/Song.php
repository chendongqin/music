<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/4
 * Time: 15:41
 */

namespace app\user\controller;

use base\Userbase;
use think\Db;

class Song extends Userbase
{

    public function index()
    {
        return $this->fetch();
    }

    //播放日志记录
    public function played()
    {
        $user = $this->getLoginUser();
        $songId = $this->getParam('songId', 0, 'int');
        if (!$song = Db::name('song')->where(['song_id' => $songId, 'is_del' => 0])->find()) {
            return $this->errorJson('歌曲不存在');
        }
        if (!Db::name('played_log')->where('user_id', $user['user_id'])->find()) {
            $res = Db::name('played_log')->insert(array('user_id' => $user['user_id']));
            if (!$res) {
                return $this->errorJson();
            }
        }
        $model = Db::name('played_log');
        Db::startTrans();
        if ($song['is_old']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_old',$song['is_old'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_popular']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_popular',$song['is_popular'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_dj']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_dj',$song['is_dj'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_classical']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_classical',$song['is_classical'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_flok']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_flok',$song['is_flok'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_rap']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_rap',$song['is_rap'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        Db::commit();
        return $this->successJson();
    }

    public function love(){
        $songId = $this->getParam('songId',0,'int');
        if (!$song = Db::name('song')->where(['song_id' => $songId, 'is_del' => 0])->find()) {
            return $this->errorJson('歌曲不存在');
        }
        $user = $this->getLoginUser();
        if($loves = Db::name('loves')->where(['user_id'=>$user['user_id'],'song_id'=>$songId])->find()){
            return $this->errorJson('已添加至收藏');
        }
        $data = array(
            'user_id'=>$user['user_id'],'song_id'=>$songId,'create_at'=>time()
        );
        if (!Db::name('loves_log')->where('user_id', $user['user_id'])->find()) {
            $res = Db::name('loves_log')->insert(array('user_id' => $user['user_id']));
            if (!$res) {
                return $this->errorJson();
            }
        }
        Db::startTrans();
        $res = Db::name('loves')->insert($data);
        if(!$res){
            Db::rollback();
            return $this->errorJson();
        }
        $model = Db::name('loves_log');
        if ($song['is_old']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_old',$song['is_old'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_popular']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_popular',$song['is_popular'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_dj']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_dj',$song['is_dj'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_classical']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_classical',$song['is_classical'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_flok']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_flok',$song['is_flok'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_rap']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('love_rap',$song['is_rap'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        Db::commit();
         return $this->successJson();
    }

}