<?php

namespace app\api\controller;

use think\Request as TypeRequest;
use think\facade\Request;
use think\exception\ValidateException;

use app\api\model\Users as UsersModel;
use app\api\validate\Users as UsersValidate;

use app\Common\Common;
use app\Common\Export;
use app\Common\BackEnd;
use jwt\Jwt;
use email\Email;
use captcha\Code;

class UserAuth
{
    /**
     * 生成账号
     *
     * @return string(10位随机数)
     */
    protected function generateNumber(): string
    {
        $length = 10;
        $characters = '0123456789';
        $user_id = '';

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $user_id .= $characters[$index];
        }

        return $user_id;
    }

    /**
     * Account检查类型
     *
     * @param string $account
     * @return array
     */
    protected function mArrayEasyCheckAccountType($account, $defult = ''): array
    {
        $tDef_Result = Common::mBoolEasyIsPhoneNumberOrEmail($account);

        if ($tDef_Result == 'phone') {
            return [
                'phone' => $account,
                'email' => $defult,
                'username' => $defult,
            ];
        } else if ($tDef_Result == 'email') {
            return [
                'phone' => $defult,
                'email' => $account,
                'username' => $defult,
            ];
        } else {
            return [
                'phone' => $defult,
                'email' => $defult,
                'username' => $account,
            ];
        };
    }

    //token校验
    public function Check()
    {
        $context = request()->JwtData;
        return Export::Create(null, 200, null, $context);
    }

    //登入-POST
    public function Login(UsersModel $userModel)
    {
        $account = Request::param('account');
        $password = Request::param('password');
        $code = Request::param('code');

        //判断是手机号还是邮箱
        $account = $this->mArrayEasyCheckAccountType($account);

        //验证器
        try {
            validate(UsersValidate::class)
                ->scene('login')
                ->remove(['email', 'username'])
                ->check([
                    'phone'  => $account['phone'],
                    'email' => $account['email'],
                    'username' => $account['username'],
                    'password' => $password,
                ]);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return Export::Create([$e->getError()], 401, '登录失败');
        }

        //账号校验请求
        $result = $userModel->Login($account, $password);
        //常规密码登入
        if ($result['status'] == false) {
            return Export::Create([$result['msg']], 401, '登录失败');
        }

        if ($code != '') {
            //验证码登入(优先)
            if (!Code::CheckCaptcha($account, strtoupper($code), 'Auth')) {
                return Export::Create(['验证码错误'], 401, '登入失败');
            };
            //清除验证码
            Code::DeleteCaptcha($account, 'Auth');
        }

        $uid = $result['data']['id'];

        $result = Jwt::signToken(['uid' => $uid]);
        return Export::Create(['token' => $result], 200);
    }

    //注册-POST
    public function Register(UsersModel $userModel)
    {
        $account = Request::param('account');
        $password = Request::param('password');
        $username = 'USER' . strtoupper(substr(md5($account . $password . time()), 0, 5));
        $code = Request::param('code');
        $number = $this->generateNumber();

        //判断是手机号还是邮箱
        $account = $this->mArrayEasyCheckAccountType($account);

        //验证器
        try {
            validate(UsersValidate::class)
                ->scene('register')
                ->check([
                    'phone'  => $account['phone'],
                    'email' => $account['email'],
                    'username' => $username,
                    'password' => $password,
                ]);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return Export::Create([$e->getError()], 401, '注册失败');
        }

        //验证码校验
        if (!Code::CheckCaptcha($account, strtoupper($code), 'Auth')) {
            return Export::Create(['验证码错误'], 401, '注册失败');
        };

        //写入数据
        $result = $userModel->Register($number, $username, $account['email'], $account['phone'], $password);
        if ($result['status'] == false) {
            return Export::Create([$result['msg']], 401, '注册失败');
        }

        $user_id = $result['data'];
        $result = Jwt::signToken(['user_id' => $user_id]);

        //清除验证码
        Code::DeleteCaptcha($account, 'Auth');

        return Export::Create(['token' => $result], 200);
    }

    //获取验证码-POST
    public function Captcha()
    {
        $account = Request::param('account');

        //判断是手机号还是邮箱
        if (Common::mBoolEasyIsPhoneNumberOrEmail($account) == 'email') {
            //获取验证码
            $data = Code::CreateCaptcha($account, 'Auth', 300);
            $code = $data['data'];
            //发送邮件
            $result = Email::SendCaptcha($code, $account);
            if ($result['status']) {
                return Export::Create(null, 200);
            } else {
                return Export::Create([$result['msg']], 401, '发送失败');
            }
        } else {
            return Export::Create(['目前仅支持邮箱验证'], 500, '发送失败');
        }
    }

    //注销-POST
    public function Logout()
    {
        return Export::Create(null, 200);
    }
}
