<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/13
 * Time: 13:26
 */

namespace ku;

use think\Session;
use think\Cache;
use think\Db;

class Algo
{
    CONST GROOMKEY = 'user_%s_groom_songs';
    private static $CONFING = [
        'new' => 3, 'hot' => 5, 'score' => 8, 'english' => 2, 'album' => 2
    ];

    public static function groom($user = null, $num = 20)
    {
        $key = sprintf(self::GROOMKEY, date('Ymd'));
        if (empty($user)) {
            if ($json = Session::get($key)) {
                return json_decode($json, true);
            }
            return self::tacit($num, $key);
        }
        $key .= '_' . $user['user_id'];
        if ($json = Cache::get($key)) {
            return json_decode($json, true);
        }
        $songs = [];
        $interesting = Db::name('interest')->where('user_id', $user['user_id'])->find();
        if (empty($interesting)) {
            Db::name('interest')->insert(['user_id' => $user['user_id']]);
            $interesting = Db::name('interest')->where('user_id', $user['user_id'])->find();
        }
        if (!$interesting['love_singer']) {
            $songs = self::getLoveSinger($interesting['love_singer']);
        }
        $playLog = Db::name('played_log')->where('user_id', $user['user_id'])->find();
        if (empty($playLog)) {
            Db::name('played_log')->insert(['user_id' => $user['user_id']]);
            $playLog = Db::name('played_log')->where('user_id', $user['user_id'])->find();
        }
        $loveLog = Db::name('loves_log')->where('user_id', $user['user_id'])->find();
        if (empty($loveLog)) {
            Db::name('loves_log')->insert(['user_id' => $user['user_id']]);
            $loveLog = Db::name('loves_log')->where('user_id', $user['user_id'])->find();
        }
        return $songs;
    }

    //默认推荐
    public static function tacit($num, $key)
    {
        $songs = [];
        //新歌
        $news = self::getNews();
        shuffle($news);
        $songs = self::divide($news, self::$CONFING['new'], $songs);
        //热搜
        $hots = self::getHots();
        shuffle($hots);
        $songs = self::divide($hots, self::$CONFING['hot'], $songs);
        //高分
        $scores = self::getScores();
        shuffle($scores);
        $songs = self::divide($scores, self::$CONFING['score'], $songs);
        //英语
        $languages = self::getLanguages();
        shuffle($languages);
        $songs = self::divide($languages, self::$CONFING['english'], $songs);
        //专辑
        $albumSongs = self::getAlbums();
        shuffle($albumSongs);
        $songs = self::divide($albumSongs, self::$CONFING['album'], $songs);
        //推荐不够时，高分填充
        $all = count($songs);
        if ($all < $num) {
            $need = $num - $all;
            shuffle($scores);
            $songs = self::divide($scores, $need, $songs);
        }
        Session::set($key, json_encode($songs), 86400);
        return $songs;
    }

    //获取新歌
    public static function getNews($num = 50)
    {
        $where = array('is_del' => 0, 'is_new' => 1);
        $order = 'create_at desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取热歌
    public static function getHots($num = 40)
    {
        $where = array('is_del' => 0);
        $order = 'select_num desc,order_by desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取高分歌
    public static function getScores($num = 80)
    {
        $where = array('is_del' => 0);
        $order = 'comments_score desc,order_by desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取语言歌
    public static function getLanguages($language = '英文', $num = 50)
    {
        $where = array('is_del' => 0, 'language' => $language);
        $order = 'order_by desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取专辑歌
    public static function getAlbums($num = 50)
    {
        $where = array('is_del' => 0, 'album_id' => ['>', 0]);
        $order = 'order_by desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取属性歌
    public static function getTypes($type = 'is_old', $high = 50, $num = 40)
    {
        $where = array('is_del' => 0, $type => ['>', $high]);
        $order = 'comments_score desc,select_num desc,order_by desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    public static function getLoveSinger($singers)
    {
        $singers = implode(',',$singers);

    }

    public static function divide(array $arr, $num, array $songs = [])
    {
        $i = 0;
        foreach ($arr as $item) {
            if ($i >= $num) {
                break;
            }
            if (isset($songs[$item['song_id']])) {
                continue;
            }
            $songs[$item['song_id']] = $item;
            $i++;
        }
        return $songs;
    }

}