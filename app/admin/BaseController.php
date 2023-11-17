<?php

namespace app\admin;

use think\facade\View;
use think\facade\Db;
use think\facade\Config;

use app\common\Common;
use app\common\Theme;

class BaseController extends Common
{
    //基础参数
    var $attrGReqTime;
    var $attrGReqIp;
    var $attrGReqView;

    function __construct()
    {
        //dd(request()->action());
        //安装检测
        @$file = fopen("../lock.txt", "r");
        if (!$file) {
            header("location:/system/install");
            exit;
        }

        $this->attrGReqTime = date('Y-m-d H:i:s');
        $this->attrGReqIp = $this->mStringGetIP();
        $this->attrGReqView = '/app/' . strtolower(request()->controller()) . '/' . request()->action();

        //公共模板变量
        View::assign([
            'LCVersionInfo' => $this->mArrayGetLCVersionInfo(), //程序版本信息
            'SystemData' => $this->mArrayGetDbSystemData(), //系统配置信息
            'SystemConfig' => config::get('lovecards'),
            'ControllerName' => strtolower(request()->controller()),
            'ActionName' => request()->action()
        ]);
    }
}
