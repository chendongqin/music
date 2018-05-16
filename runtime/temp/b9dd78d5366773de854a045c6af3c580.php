<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:80:"D:\phpstudy\WWW\basketballClub\public/../application/user\view\login\index.phtml";i:1523335525;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>用户登陆</title>
    <script type="text/javascript" src="/static/js/jq.js"></script>
    <script type="text/javascript" src="/static/js/user/login.js"></script>
    <link rel="stylesheet" type="text/css" href="/assets/css/amazeui.min.css" />
    <link rel="stylesheet" type="text/css" href="/assets/css/app.css" />
    <style>
        .bg{
            background: url(/assets/i/bg/login1.jpg);
            background-size:1400px;
            background-repeat:no-repeat;
        }
        .header {
            text-align: center;
        }
        .header h1 {
            font-size: 200%;
            color: #aa4b00;
            margin-top: 30px;
        }
        .header p {
            font-size: 14px;
        }
        .am-f-center {
            text-align: center;
        }
        .am-captcha-text{
            width:110px;
        }
        .am-color{
            color: #14a6ef;
        }
    </style>
</head>
<body class="bg">
<div class="header">
    <div class="am-g">
        <a href="/"><h1>BasketballClubs</h1></a>
       <p class="am-color">The game became simpler and the data became clearer<br/>比赛变得更简单，数据变得更清晰</p>
    </div>
    <hr  />
</div>
<div class="am-f-center">
    <form name="loginForm" id="loginForm">
        <label class="am-color" for="email">账号：</label>
        <input type="text" placeholder="请输入邮箱" name="email" id="email">
        <br />
        <label class="am-color" for="password">密码：</label>
        <input type="password" placeholder="请输入密码" name="password" id="password">
        <br />
        <label class="am-color" for="captcha">验证码：</label>
        <input class="am-captcha-text" type="text"  name="captcha" id="captcha">
        <a href="javascript:;" id="changeCaptcha" title = "点击更换验证码"><img src="/index/captcha?channel=login" id="captchaImg"></a>
        <br />
        <input type="button" id="login" value="登陆">
    </form>
    <br />
    <a href="/user/login/forget">忘记密码</a>
</div>
</body>
</html>