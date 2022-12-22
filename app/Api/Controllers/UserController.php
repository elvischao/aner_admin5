<?php
namespace App\Api\Controllers;

use Illuminate\Http\Request;

use App\Api\Controllers\BaseController;

use App\Api\Services\UserService;


class UserController extends BaseController{
    /**
     * 会员信息详情
     *
     * @return void
     */
    public function detail(){
        $data = (new UserService())->get_user_detail($this->uid);
        return success('会员详情', $data);
    }

    /**
     * 修改会员信息
     *
     * @param Request $request
     * @return void
     */
    public function update_data(Request $request){
        $update_data['nickname'] = $request->input('nickname', null) ?? null;
        $res = (new UserService())->update_datas($this->uid, $update_data);
        return $res ? success('编辑成功') : error('编辑失败');
    }

    /**
     * 修改密码，输入旧密码和新密码
     *
     * @param \App\Api\Requests\Password\UpdatePasswordRequest $request
     * @return void
     */
    public function update_password(\App\Api\Requests\Password\UpdatePasswordRequest $request){
        $password = $request->input('password');
        $res = (new UserService())->update_datas($this->uid, ['password'=> $password]);
        return $res ? success('密码修改成功') : error('密码修改失败');
    }

    /**
     * 忘记密码，输入手机验证码和新密码
     *
     * @param \App\Api\Requests\Password\ForgetPasswordRequest $request
     * @return void
     */
    public function forget_password(\App\Api\Requests\Password\ForgetPasswordRequest $request){
        $password = $request->input('password');
        $res = (new UserService())->update_datas($this->uid, ['password'=> $password]);
        return $res ? success('密码修改成功') : error('密码修改失败');
    }

    /**
     * 修改二级密码(支付密码)，输入旧密码和密码
     *
     * @param \App\Api\Requests\Password\UpdateLevelPasswordRequest $request
     * @return void
     */
    public function update_level_password(\App\Api\Requests\Password\UpdateLevelPasswordRequest $request){
        $password = $request->input('password');
        $res = (new UserService())->update_datas($this->uid, ['level_password'=> $password]);
        return $res ? success('二级密码修改成功') : error('二级密码修改失败');
    }

    /**
     * 忘记二级密码，输入短信验证码与新密码
     *
     * @param \App\Api\Requests\Password\ForgetLevelPasswordRequest $request
     * @return void
     */
    public function forget_level_password(\App\Api\Requests\Password\ForgetLevelPasswordRequest $request){
        $password = $request->input('password');
        $res = (new UserService())->update_datas($this->uid, ['level_password'=> $password]);
        return $res ? success('二级密码修改成功') : error('二级密码修改失败');
    }
}
