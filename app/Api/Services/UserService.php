<?php
namespace App\Api\Services;


use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

use App\Api\Repositories\User\UsersRepository;
use App\Api\Repositories\User\UserDetailRepository;
use Illuminate\Support\Facades\DB;

class UserService{
    // 会员表中的数据
    protected $user_fields = ['id', 'avatar', 'nickname', 'phone'];
    // 会员表中要被查询的数据
    protected $user_select = ['id', 'avatar', 'nickname', 'phone'];
    // 会员详情表中的数据
    protected $user_detail_fields = ['id_card_username'];
    // 会员详情表中国呢要被查询的数据
    protected $user_detail_select = ['id_card_username'];


    /**
     * 通过token获取到会员id
     *
     * @param string $token token
     * @return int
     */
    public function use_token_get_uid(string $token):int{
        return (new UsersRepository())->use_token_get_uid($token);
    }

    /**
     * 获取会员详情
     *
     * @param int $uid 会员id
     * @return json
     */
    public function get_user_detail(int $uid){
        $user_data = (new UsersRepository())->use_field_get_data([['id', '=', $uid]], $this->user_select);
        $user_detail_select = (new UserDetailRepository())->use_field_get_data([['id', '=', $uid]],$this->user_detail_select);
        foreach($user_detail_select as $key=> $value){
            $user_data->$key = $value;
        }
        return $user_data;
    }

    /**
     * 修改会员数据（指定字段）
     *
     * @param int $uid 会员id
     * @param array $datas
     * @return bool
     */
    public function update_datas(int $uid, array $datas = []){
        // 特殊字段处理
        if(!empty($datas['password'])){
            $datas['password'] = (new UsersRepository())->set_password($datas['password']);
        }
        DB::beginTransaction();
        try{
            // 筛选出会员表的数据并修改
            $update_data = [];
            foreach($this->user_fields as $field){
                if(!empty($datas[$field])){
                    $update_data[$field] = $datas[$field];
                }
            }
            if(count($update_data) >= 1){
                $res = (new UsersRepository())->update_data([['id', '=', $uid]], $update_data);
                if(!$res){
                    throwBusinessException('修改失败');
                }
            }
            // 筛选出会员详情表的数据并修改
            $update_data = [];
            foreach($this->user_detail_fields as $field){
                if(!empty($datas[$field])){
                    $update_data[$field] = $datas[$field];
                }
            }
            if(count($update_data) >= 1){
                $res = (new UserDetailRepository())->update_data([['id', '=', $uid]], $update_data);
                if(!$res){
                    throwBusinessException('修改失败');
                }
            }
            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            throwBusinessException($e->getMessage());
        }
        return true;
    }
}