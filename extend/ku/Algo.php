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

    private static $songType = [
        'old', 'popular', 'classical', 'dj', 'flok', 'rap'
    ];

    //推荐歌曲
    public static function groom($user = null, $num = 20)
    {
        //查看缓存是否命中，命中则返回缓存值
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
        $emptyInterest = false;
        $emptyPlayedLog = false;
        $emptyLovesLog = false;
        $interesting = Db::name('interest')->where('user_id', $user['user_id'])->find();
        if (empty($interesting)) {
            $emptyInterest = true;
            Db::name('interest')->insert(['user_id' => $user['user_id']]);
            $interesting = Db::name('interest')->where('user_id', $user['user_id'])->find();
        }
        $playLog = Db::name('played_log')->where('user_id', $user['user_id'])->find();
        if (empty($playLog)) {
            $emptyPlayedLog = true;
            Db::name('played_log')->insert(['user_id' => $user['user_id']]);
            $playLog = Db::name('played_log')->where('user_id', $user['user_id'])->find();
        }
        $loveLog = Db::name('loves_log')->where('user_id', $user['user_id'])->find();
        if (empty($loveLog)) {
            $emptyLovesLog = true;
            Db::name('loves_log')->insert(['user_id' => $user['user_id']]);
            $loveLog = Db::name('loves_log')->where('user_id', $user['user_id'])->find();
        }
        if ($emptyInterest && $emptyPlayedLog && $emptyLovesLog) {
            $songs = self::tacit($num, '', $songs);
            //缓存到每天6:00过期
            $tomorrow = date('Y-m-d',strtotime('+1 day'));
            $tomorrow .= ' 06:00:00';
            $tomorrowTime = strtotime($tomorrow);
            $prefix = $tomorrowTime - time();
            Cache::set($key, json_encode($songs), $prefix);
            return $songs;
        }
        if (!$interesting['love_singer']) {
            $loveSongs = self::getLoveSinger($interesting['love_singer']);
            shuffle($loveSongs);
            if(count($loveSongs) >=50){
                $divideNum = 5;
            }else{
                $divideNum = (int)(count($loveSongs) / 10);
            }
            $songs = self::divide($loveSongs, $divideNum, $songs);
        }
        if ($interesting['love_new']) {
            $news = self::getNews();
            shuffle($news);
            $songs = self::divide($news, 4, $songs);
        }
        $typeSocre = [];
        $total = 0;
        $playedTotal = 0;
        foreach (self::$songType as $type) {
            if ($interesting['love_' . $type]) {
                $typeSocre[$type] += bcmul($playLog['played_' . $type], 0.4) + bcmul($loveLog['love_' . $type], 0.6);
            } else {
                $typeSocre[$type] = bcmul($playLog['played_' . $type], 0.7) + bcmul($loveLog['love_' . $type], 0.3);
            }
            $total += $typeSocre[$type];
            $playedTotal += $playLog['played_' . $type];
        }
        $total = $total == 0 ? 1 : $total;
        //播放的歌曲小于20首，按爱好来分
        if ($playedTotal < 2000) {
            //喜欢的类型有几种,均分
            $loveTypesTotal = $interesting['love_old'] + $interesting['love_popular'] + $interesting['love_dj'] + $interesting['love_classical'] + $interesting['love_flok'] + $interesting['love_rap'];
            $loveTypesTotal = $loveTypesTotal == 0 ? 1 : $loveTypesTotal;
            $divideNum = (int)(($num - count($songs)) / $loveTypesTotal);
            foreach (self::$songType as $type) {
                if ($interesting['love_' . $type]) {
                    $typeSongs = self::getTypes('is_' . $type);
                    shuffle($typeSongs);
                    $songs = self::divide($typeSongs, $divideNum, $songs);
                }
            }
        } else {
            $newTotal = $total;
            foreach ($typeSocre as $key => $score) {
                if ($score / $total < 0.15) {
                    unset($typeSocre[$key]);
                    $newTotal -= $score;
                }
            }
            $needs = $num - count($songs);
            foreach ($typeSocre as $key => $score) {
                $divideNum = (int)($score / $newTotal * $needs);
                $typeSongs = self::getTypes('is_' . $key);
                shuffle($typeSongs);
                $songs = self::divide($typeSongs, $divideNum, $songs);
            }
        }
        $needs = $num - count($songs);
        if ($needs > 0) {
            $hots = self::getHots();
            shuffle($hots);
            $songs = self::divide($hots, $needs, $songs);
        }
        //缓存到每天6:00过期
        $tomorrow = date('Y-m-d',strtotime('+1 day'));
        $tomorrow .= ' 06:00:00';
        $tomorrowTime = strtotime($tomorrow);
        $prefix = $tomorrowTime - time();
        Cache::set($key, json_encode($songs), $prefix);
        return $songs;
    }

    //默认推荐
    public static function tacit($num, $key = '', $songs = [])
    {
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
        if($key){
            //缓存到每天6:00过期
            $tomorrow = date('Y-m-d',strtotime('+1 day'));
            $tomorrow .= ' 06:00:00';
            $tomorrowTime = strtotime($tomorrow);
            $prefix = $tomorrowTime - time();
            Session::set($key, json_encode($songs), $prefix);
        }

        return $songs;
    }

    //获取新歌
    public static function getNews($num = 100)
    {
        $where = array('is_del' => 0, 'is_new' => 1);
        $order = 'song_id desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取热歌
    public static function getHots($num = 100)
    {
        $where = array('is_del' => 0);
        $order = 'played desc,song_id desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取高分歌
    public static function getScores($num = 100)
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
    public static function getLanguages($language = '英文', $num = 60)
    {
        $where = array('is_del' => 0, 'language' => $language);
        $order = 'comments_score desc,played desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取专辑歌
    public static function getAlbums($num = 60)
    {
        $where = array('is_del' => 0, 'album_id' => ['>', 0]);
        $order = 'played desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    //获取属性歌
    public static function getTypes($type = 'is_old', $byScore = false, $high = 60, $num = 100)
    {
        $where = array('is_del' => 0, $type => ['>', $high]);
        $order = $byScore ? 'comments_score desc,order_by desc' : 'played desc,order_by desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
    }

    public static function getLoveSinger($singers, $num = 60)
    {
        $songs = [];
        if (empty($singers)) {
            return $songs;
        }
        $singers = implode(',', $singers);
        $where = 'is_del=0 ';
        $str = '(';
        foreach ($singers as $singer) {
            $str .= " singer = '" . $singer . "' or";
        }
        $str = rtrim($str, 'or') . ')';
        $where .= 'and ' . $str;
        $order = 'song_id desc';
        $songs = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($num)
            ->select();
        return $songs;
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