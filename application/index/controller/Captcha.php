<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/4
 * Time: 14:48
 */

namespace app\index\controller;

use base\Base;
use ku\Tool;
use think\Cache;
use think\Session;
use ku\Verify;

class Captcha extends Base
{

    public function index()
    {
        $request = $this->request;
        $channel = $request->param('channel', '', 'string');
        Tool::captcha(4, 100, 40, $channel);
    }


    public function sms()
    {
        $channel = $this->getParam('channel', '');
        $mobile = $this->getParam('mobile', '');
        $code = $this->getParam('code', '');
        if (!Verify::isMobile($mobile)) {
            return $this->errorJson('手机号不正确');
        }
        // $virefyCode = Session::get($channel . '_virefy_code');
        // if ($code != strtolower($virefyCode)) {
        //     return $this->errorJson('验证码错误');
        // }
        $sendVirefy = Cache::get($channel . 'send' . $mobile);
        if ($sendVirefy) {
            return $this->errorJson('发送太频繁');
        }
        $mobileCode = Tool::randCode(4, false, false);
        $mobileCode = '0000';
        Cache::set($channel . $mobile, $mobileCode, 300);
        Cache::set($channel . 'send' . $mobile, 1, 60);
        return $this->successJson();
    }

}