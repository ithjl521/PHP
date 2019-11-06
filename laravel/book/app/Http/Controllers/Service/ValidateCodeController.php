<?php

namespace App\Http\Controllers\Service;

use App\Entity\Member;
use App\Entity\TempEmail;
use App\Entity\TempPhone;
use App\Models\M3Result;
use App\Tool\SMS\SendTemplateSMS;
use App\Tool\Validate\ValidateCode;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ValidateCodeController extends Controller
{
    /**
     * 生成验证码
     * @param Request $request
     */
    public function create(Request $request)
    {
        $validateCode = new ValidateCode();
        $request->session()->put('validate_code',$validateCode->getCode());

        return $validateCode->doimg();
    }

    /**
     * 发送短信
     * @param Request $request
     * @return string
     */
    public function sendSMS(Request $request)
    {
        $phone = $request->input('phone','');
        if ($phone == '') {
            $m3Result = new M3Result();
            $m3Result->status = 1;
            $m3Result->message = '失败';

            return $m3Result->toJson();
        }

        $sendTemplateSMS = new SendTemplateSMS();
        $code = '';

        $charset = '0123456789';
        $_len = strlen($charset)- 1;
        for ($i=1;$i<=6;++$i) {
            $code .= $charset[mt_rand(0,$_len)];
        }

        $m3Result = $sendTemplateSMS->sendTemplateSMS($phone,array($code,60),1);

        //将验证码保存到数据库
        $tempPhone = new TempPhone();
        $tempPhone->phone = $phone;
        $tempPhone->code = $code;
        $tempPhone->deadline = date('Y-m-d H:i:s',time()+3600);
        $tempPhone->save();

        /*$m3Result = new M3Result();
        $m3Result->status = 0;
        $m3Result->message = '成功';*/

        return $m3Result->toJson();
    }


    /**
     * desc 邮箱激活
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|string
     */
    public function validateEmail(Request $request)
    {
        $member_id = $request->input('member_id','');
        $code = $request->input('code','');

        if ($member_id == '' || $code == '') {
            return '验证异常';
        }

        $tempEmail = TempEmail::where('member_id',$member_id)->orderBy('id','desc')->first();

        if ($tempEmail == null) {
            return '验证异常';
        }

        if ($tempEmail->code == $code) {
            if (time() > strtotime($tempEmail->deadline)) {
                return '该链接已失效';
            }

            $member = Member::find($member_id);
            $member->active = 1;
            $member->save();
            return redirect('/login');
        } else {
            return '该链接已失效';
        }


    }
}
