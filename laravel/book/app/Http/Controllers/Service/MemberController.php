<?php

namespace App\Http\Controllers\Service  ;

use App\Entity\Member;
use App\Entity\TempEmail;
use App\Entity\TempPhone;
use App\Http\Controllers\Controller;
use App\Models\M3Email;
use App\Models\M3Result;
use App\Tool\UUID;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MemberController extends Controller
{
    /**
     * desc 注册接口
     * @param Request $request
     * @return string
     */
    public function register(Request $request)
    {
        $email = $request->input('email','');
        $phone = $request->input('phone','');
        $password = $request->input('password','');
        $confirm = $request->input('confirm','');
        $phone_code = $request->input('phone_code','');
        $validate_code = $request->input('validate_code','');

        $m3_result = new M3Result();

        if ($email == '' && $phone == '') {
            $m3_result->status = 1;
            $m3_result->message = '邮箱或手机号不能为空';
            return $m3_result->toJson();
        }
        if ($password == '' || strlen($password) < 6) {
            $m3_result->status = 2;
            $m3_result->message = '密码不能少于6位';
            return $m3_result->toJson();
        }
        if ($confirm == '' || strlen($confirm) < 6) {
            $m3_result->status = 3;
            $m3_result->message = '确认密码不能少于6位';
            return $m3_result->toJson();
        }
        if ($password != $confirm) {
            $m3_result->status = 4;
            $m3_result->message = '两次密码不一致';
            return $m3_result->toJson();
        }

        //手机号注册
        if ($phone != '') {
            if ($phone_code == '' || strlen($phone_code) != 6) {
                $m3_result->status = 5;
                $m3_result->message = '手机验证码为6位';
                return $m3_result->toJson();
            }

            $tempPhone = TempPhone::where('phone',$phone)->orderBy('id', 'DESC')->first();

            $member = Member::where('phone',$phone)->first();
            //验证手机号是否已经被注册
            if ($member) {
                $m3_result->status = 9;
                $m3_result->message = '手机号已经被注册';
                return $m3_result->toJson();
            }

            if ($tempPhone->code == $phone_code) {
                if (time() > strtotime($tempPhone->deadline)) {
                    $m3_result->status = 7;
                    $m3_result->message = '手机验证码不正确';
                    return $m3_result->toJson();
                }

                $member = new Member();
                $member->phone = $phone;
                $member->password = md5('bk' . $password);
                $member->save();

                $m3_result->status = 0;
                $m3_result->message = '注册成功';
                return $m3_result->toJson();

            } else {
                $m3_result->status = 7;
                $m3_result->message = '手机验证码不正确';
                return $m3_result->toJson();
            }


        //邮箱注册
        } else {
            if ($validate_code == '' || strlen($validate_code) != 4) {
                $m3_result->status = 6;
                $m3_result->message = '验证码为4位';
                return $m3_result->toJson();
            }

            $validate_code_session = $request->session()->get('validate_code','');
            if ($validate_code_session != $validate_code) {
                $m3_result->status = 8;
                $m3_result->message = '验证码不正确';
                return $m3_result->toJson();
            }

            //验证邮箱是否被注册
            $member = Member::where('email',$email)->first();
            if ($member) {
                $m3_result->status = 9;
                $m3_result->message = '邮箱已经被注册';
                return $m3_result->toJson();
            }

            //保存注册数据
            $member = new Member();
            $member->email = $email;
            $member->password = md5('bk' . $password);
            $member->save();

            $uuid = UUID::create();

            $m3_email = new M3Email();
            $m3_email->to = $email;
            $m3_email->cc = 'it_hjl@163.com';
            $m3_email->subject = 'book验证码';
            $m3_email->content = '请24小时内点击该链接完成验证。book.com/service/validate_email'
                                .'?member_id='.$member->id
                                . '&code='.$uuid;

            //保存邮箱需要的激活数据
            $tempEmail = new TempEmail();
            $tempEmail->member_id = $member->id;
            $tempEmail->code = $uuid;
            $tempEmail->deadline = date('Y-m-d H:i:s',time()+24*3600);
            $tempEmail->save();

            //激活邮件的发送
            Mail::send(
                'email_register',
                ['m3_email' => $m3_email],
                function ($m) use($m3_email) {

                    $m->from('it_hjl@163.com','book');
                    $m->to($m3_email->to)
                        ->cc($m3_email->cc)
                        ->subject($m3_email->subject);
                }
            );


            $m3_result->status = 0;
            $m3_result->message = '注册成功';
            return $m3_result->toJson();

        }


    }


    public function login(Request $request)
    {
        $username = $request->get('username','');
        $password = $request->get('password','');
        $validate_code = $request->get('validate_code','');

        $m3_result = new M3Result();

        //校验


        //判断
        $validate_code_session = $request->session()->get('validate_code');
        if ($validate_code != $validate_code_session) {
            $m3_result->status = 1;
            $m3_result->message = '验证码不正确';
            return $m3_result->toJson();
        }

        $member = null;
        if (strpos($username,'@') == true) {
            //邮箱登录
            $member = Member::where('email',$username)->first();
        } else {
            //手机号登录
            $member = Member::where('phone',$username)->first();
        }

        if ($member == null) {
            $m3_result->status = 2;
            $m3_result->message = '用户不存在';
            return $m3_result->toJson();
        } else {
            if ($member->password != md5('bk'.$password)) {
                $m3_result->status = 3;
                $m3_result->message = '密码不正确';
                return $m3_result->toJson();
            }
        }

        $request->session()->put('member',$member);

        $m3_result->status = 0;
        $m3_result->message = '登录成功';
        return $m3_result->toJson();

    }
}
