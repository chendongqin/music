<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/4/1
 * Time: 10:51
 */
namespace app\user\controller;
use base\Userbase;
use think\Db;

class Club extends Userbase{

    public function index(){
        $id= $this->request->param('id','','int');
        $user =$this->getUser();
        $myClub = Db::name('club')->where('Id',$id)->find();
        if(empty($myClub))
            return $this->fetch(APP_PATH.'index/view/error.phtml',['error'=>'球队不存在']);
        $players = json_decode($myClub['players'],true);
        if(!isset($players[$user['Id']]))
            return $this->fetch(APP_PATH.'index/view/error.phtml',['error'=>'您未加入该球队']);
        $this->assign('club',$myClub);
        $this->assign('players',$players);
        return $this->fetch();
    }

    public  function player(){
        $Id= $this->request->param('id','','int');
        $user = Db::name('user')->where('Id',$Id)->find();
        if(empty($user))
            return $this->fetch(APP_PATH.'index/view/error.phtml',['error'=>'没有该球员']);
        $this->assign('user',$user);
        return $this->fetch();
    }

    //加入赛事比赛
    public function joinEvent(){
        $id = $this->request->param('id','','int');
        $clubId = $this->request->param('clubId','','int');
        $code = $this->request->param('code','','string');
        $event = Db::name('event')->where('Id',$id)->find();
        if(empty($event))
            return $this->returnJson('赛事不存在');
        $user = $this->getUser();
        $club = Db::name('club')->where('Id',$clubId)->find();
        if(empty($club))
            return $this->returnJson('没有该球队');
        if($club['captain'] != $user['Id'])
            return $this->returnJson('您不是队长，没有权限加入比赛');
        if(strcmp($code,$event['virefy_code'])!==0)
            return $this->returnJson('邀请码错误，请重新确认');
        $joins = json_decode($event['join_clubs'],true);
        if(isset($joins[$clubId]))
            return $this->returnJson('已经加入比赛，无需重复操作');
        $joins[$clubId] = $club['name'];
        $update = ['Id'=>$event['Id'],'join_clubs'=>json_encode($joins)];
        $res = Db::name('event')->update($update);
        if(!$res)
            return $this->returnJson('加入比赛失败，请重试');
        return $this->returnJson('加入比赛成功',true,1);

    }

    //获取队长可参加该比赛的队伍
    public function captainClub(){
        $eventId = $this->request->param('eventId','','int');
        $event = DB::name('event')->where('Id',$eventId)->find();
        if(empty($event))
            return $this->returnJson('赛事不存在');
        $joins = json_decode($event['join_clubs'],true);
        $clubIds = empty($joins)?array():array_keys($joins);
        $user = $this->getUser();
        $clubs = Db::name('club')
            ->where('captain',$user['Id'])
            ->where('Id','not in',$clubIds)
            ->select();
        if(empty($clubs))
            return $this->returnJson("没有可加入的队伍");
        return $this->returnJson('获取成功',true,1,$clubs);
    }

    //更换队长
    public function changeCaptain(){
        $user = $this->getUser();
        $id = $this->request->param('id','','int');
        $playerId = $this->request->param('playerId','','int');
        $club = Db::table('club')->where('Id',$id)->find();
        if (empty($club))
            return $this->returnJson('球队不存在');
        if($user['Id'] != $club['captain'])
            return $this->returnJson('您不是队长，无法操作');
        $players = json_decode($club['players'],true);
        if(!isset($players[$playerId]))
            return $this->returnJson('该用户不在球队');
        $update = ['Id'=>$id,'captain'=>$playerId];
        $res = Db::name('club')->update($update);
        if(!$res)
            return $this->returnJson('操作失败，请重试');
        return $this->returnJson('更换成功',true,1);

    }

}