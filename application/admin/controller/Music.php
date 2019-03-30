<?php
namespace app\admin\controller;
use base\Adminbase;
use ku\Tool;
use think\Db;
class Music extends Adminbase
{
    //歌曲列表
    public function index()
    {
        $name = $this->getParam('name', '', 'string');
        $pageLimit = $this->getParam('pageLimit', 15, 'int');
        $page = $this->getParam('page', 1, 'int');
        $type = $this->getParam('type', 0, 'int');
        $isdel = $this->getParam('isdel', 0, 'int');
        $where = $this->typeToWhere($type);
        $where['is_del'] = $isdel;
        if ($name) {
            $where['song_name|singer'] = array('like',  $name . '%');
        }
        $language = $this->getParam('language', '', 'string');
        if($language){
            $where['language'] = $language;
        }
        $pager = Db::name('song')->where($where)->paginate($pageLimit, false, array('page' => $page))->toArray();
        $this->assign('pager', $pager);
        $this->assign('pageLimit', $pageLimit);
        $this->assign('page', $page);
        $this->assign('musicTypes',$this->_musicTypes);
        $this->assign('musicLanguages',$this->_musicLanguages);
        return $this->fetch();
    }

    //添加音乐
    public function add()
    {
        $data['song_name'] = $this->getParam('song_name');
        $data['singer'] = $this->getParam('singer');
        $data['album_id'] = $this->getParam('album_id');
        $data['lyrics'] = $this->getParam('lyrics');
        $data['language'] = $this->getParam('language');
        $issueDate = $this->getParam('issue_time',date('Y-m-d'));
        $data['issue_time'] = date('Y-m-d',strtotime($issueDate));
        $data['is_old'] = $this->getParam('is_old',0,'int');
        $data['is_popular'] = $this->getParam('is_popular',0,'int');
        $data['is_dj'] = $this->getParam('is_dj',0,'int');
        $data['is_classical'] = $this->getParam('is_classical',0,'int');
        $data['is_flok'] = $this->getParam('is_flok',0,'int');
        $data['is_rap'] = $this->getParam('is_rap',0,'int');
        $data['is_new'] = $this->getParam('is_new',0,'int');
        $data['order_by'] = $this->getParam('order_by',0,'int');
        //歌曲属性的权重和必须等于100
        if((int)($data['is_old'] + $data['is_popular']+ $data['is_dj']+ $data['is_classical']+ $data['is_flok']+ $data['is_rap']) != 100){
            return $this->returnJson('歌曲属性的权重和必须等于100');
        }
        if(empty($data['song_name']) || empty($data['singer'])){
            return $this->returnJson('歌名和歌手不能为空');
        }
        if ($exist = Db::name('song')->where(array('song_name'=>$data['song_name'],'singer'=>$data['singer'],'is_del'=>0))->find()){
            return $this->returnJson('已存在该歌手的同名歌曲');
        }
        if(!in_array($data['language'],$this->_musicLanguages))
            $data['language'] = '其他';
        $request = $this->request;
        //上传音乐文件
        $songOrigin = $request->file('songOrigin');
        if(empty($songOrigin))
            return $this->returnJson('音乐原文件不能为空');
        //生成目录
        $dir = ROOT_PATH.'public'.DS.'uploads'.DS.'music_origin';
        Tool::makeDir($dir);
        $info = $songOrigin->move($dir);
        $filename = $info->getFilename();
        if(!$filename){
            return $this->returnJson($songOrigin->getError());
        }
        $filenameArr = explode('.',$filename);
        if(!in_array(strtoupper(end($filenameArr)),$this->_musicOrigins)){
            unlink(ROOT_PATH.'public'.DS.'uploads'.DS.'music_origin'.DS.date('Ymd').DS.$filename);
            return $this->returnJson('音乐源文件类型为：'.implode('，',$this->_musicOrigins));
        }
        $data['song_origin'] = DS.'music_origin'.DS.date('Ym').DS.$filename;
        //上传音乐图片
        $songOrigin = $request->file('songPicture');
        if(empty($songOrigin)){
            unlink(ROOT_PATH.'public'.DS.'uploads'.$data['song_origin']);
            return $this->returnJson('音乐图片不能为空');
        }
        $dir = ROOT_PATH.'public'.DS.'uploads'.DS.'music_picture';
        Tool::makeDir($dir);
        $info = $songOrigin->move($dir);
        $filename = $info->getFilename();
        if(!$filename){
            unlink(ROOT_PATH.'public'.DS.'uploads'.$data['song_origin']);
            return $this->returnJson($songOrigin->getError());
        }
        $filenameArr = explode('.',$filename);
        if(!in_array(strtoupper(end($filenameArr)),$this->_musicOrigins)){
            unlink(ROOT_PATH.'public'.DS.'uploads'.$data['song_origin']);
            unlink(ROOT_PATH.'public'.DS.'uploads'.DS.'music_picture'.DS.date('Ymd').DS.$filename);
            return $this->returnJson('音乐图片文件类型为：'.implode('，',$this->_musicOrigins));
        }
        $data['song_picture'] = DS.'music_origin'.DS.date('Ym').DS.$filename;
        Db::startTrans();
        if(!$singer = Db::name('singer')->where('singer_name',$data['singer'])->find()){
            $res = Db::name('singer')->insert(array('singer_name'=>$data['singer']));
            if(!$res){
                Db::rollback();
                return $this->returnJson('失败');
            }
        }
        $res = Db::name('song')->insert($data);
        if(!$res){
            Db::rollback();
            return $this->returnJson('失败');
        }
        Db::commit();
        return $this->returnJson('成功',1001,true);
    }

    //数据更新
    public function update()
    {
        $song_id = $this->getParam('song_id',0,'int');
        if(!$song = Db::name('song')->where('song_id',$song_id)->find()){
            return $this->returnJson('歌曲不存在');
        }
        $song['song_name'] = $this->getParam('song_name');
        $song['singer'] = $this->getParam('singer');
        $song['album_id'] = $this->getParam('album_id');
        $song['lyrics'] = $this->getParam('lyrics');
        $song['language'] = $this->getParam('language');
        $issueDate = $this->getParam('issue_time',date('Y-m-d'));
        $song['issue_time'] = date('Y-m-d',strtotime($issueDate));
        $song['is_old'] = $this->getParam('is_old',0,'int');
        $song['is_popular'] = $this->getParam('is_popular',0,'int');
        $song['is_dj'] = $this->getParam('is_dj',0,'int');
        $song['is_classical'] = $this->getParam('is_classical',0,'int');
        $song['is_flok'] = $this->getParam('is_flok',0,'int');
        $song['is_rap'] = $this->getParam('is_rap',0,'int');
        $song['is_new'] = $this->getParam('is_new',0,'int');
        $song['order_by'] = $this->getParam('order_by',0,'int');
        //歌曲属性的权重和必须等于100
        if((int)($song['is_old'] + $song['is_popular']+ $song['is_dj']+ $song['is_classical']+ $song['is_flok']+ $song['is_rap']) != 100){
            return $this->returnJson('歌曲属性的权重和必须等于100');
        }
        if(empty($song['song_name']) || empty($song['singer'])){
            return $this->returnJson('歌名和歌手不能为空');
        }
        if ($exist = Db::name('song')->where(array('song_name'=>$song['song_name'],'singer'=>$song['singer'],'is_del'=>0,'song_id'=>array('<>',$song_id)))->find()){
            return $this->returnJson('已存在该歌手的同名专辑');
        }
        if(!in_array($song['language'],$this->_musicLanguages))
            $song['language'] = '其他';
        $res = Db::name('song')->update($song);
        if(!$res){
            return $this->returnJson('失败');
        }
        return $this->returnJson('成功',1001,true);
    }

    //删除
    public function delete()
    {
        $song_id = $this->getParam('song_id',0,'int');
        if(!$song = Db::name('song')->where('song_id',$song_id)->find()){
            return $this->returnJson('歌曲不存在');
        }
        $song['is_del'] = 1;
        $res = Db::name('song')->update($song);
        if(!$res){
            return $this->returnJson('失败');
        }
        return $this->returnJson('成功',1001,true);
    }



}