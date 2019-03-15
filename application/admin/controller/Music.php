<?php
namespace app\admin\controller;
use base\Adminbase;
use ku\Tool;
use think\Db;
class Music extends Adminbase
{

    public function index()
    {
        $name = $this->getParam('name', '', 'string');
        $pageLimit = $this->getParam('pageLimit', 15, 'int');
        $page = $this->getParam('page', 1, 'int');
        $type = $this->getParam('type', 0, 'int');
        $where = $this->typeToWhere($type);
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
    public function add(){
        $data['song_name'] = $this->getParam('song_name');
        $data['singer'] = $this->getParam('singer');
        $data['album_id'] = $this->getParam('album_id');
        $data['lyrics'] = $this->getParam('lyrics');
        $data['language'] = $this->getParam('language');
        $data['issue_time'] = date('YmdHis',strtotime($this->getParam('issue_time')));
        $data['is_old'] = $this->getParam('is_old',0,'int');
        $data['is_popular'] = $this->getParam('is_popular',0,'int');
        $data['is_dj'] = $this->getParam('is_dj',0,'int');
        $data['is_classical'] = $this->getParam('is_classical',0,'int');
        $data['is_flok'] = $this->getParam('is_flok',0,'int');
        $data['is_rap'] = $this->getParam('is_rap',0,'int');
        $data['is_new'] = $this->getParam('is_new',0,'int');
        $data['order_by'] = $this->getParam('order_by',0,'int');
        if(empty($data['song_name']) || empty($data['singer'])){
            return $this->returnJson('歌名和歌手不能为空');
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



}