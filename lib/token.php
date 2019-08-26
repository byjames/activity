<?php
header("Content-type: text/html; charset=utf-8");
include "config.php";
include "mysql.lib.php";

class Token
{
    public $wechat_error;
    public $wechat_code;
    public $wechat_url;
    public $token;
    public $openid;
    public $access_info;

    public function __construct($error_code = false)
    {
        $this->wechat_error = $error_code;
        $this->GetWechatAccessToken();
        $this->SetOpenID();
    }

    /****
     * 获取 wechat code
     */
    public function GetWechatCode()
    {
        $url = WECHAT_SERVER . '?appid=' . WECHAT_APPID . '&redirect_uri=' . WECHAT_REDIRECT_URL . '&response_type=code&scope=' . WECHAT_SCOPE_BASE . '&state=1#wechat_redirect';

        $this->wechat_code = file_get_contents($url);
        $this->wechat_url = $url;
        return $this->wechat_code;
    }

    public function GetWechatAccessToken()
    {
        $this->GetWechatCode();
        $url = WECHAT_ACCESSTOKEN_URL . '?appid=' . WECHAT_APPID . '&secret=' . WECHAT_KEY . '&code=' . $this->wechat_code . '&grant_type=authorization_code';
        $this->access_info = file_get_contents($url);
        return $this->access_info;
    }

    public function SetOpenID($data)
    {
        $this->openid = json_decode($data, true)['openid'];
    }


}
/*
$token = new Token();

$cc = $token->GetWechatCode();
error_log("code#######" . $cc, '3', 'D:\xampp\htdocs\wechat\log\error.txt');

error_log("wechat_url#######" . $token->wechat_url, '3', 'D:\xampp\htdocs\wechat\log\error.txt');

var_dump($token->wechat_code);*/