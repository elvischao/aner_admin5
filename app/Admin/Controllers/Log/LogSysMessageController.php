<?php

namespace App\Admin\Controllers\Log;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

use App\Admin\Controllers\BaseController;

use App\Admin\Repositories\Log\LogSysMessage;
use App\Admin\Repositories\User;

use App\Models\User\Users;

/**
 * 系统消息，mysql 作为存储，redis 保存每个会员的消息id与全员消息
 */
class LogSysMessageController extends BaseController{
    protected int $id;
    protected string $uids;
    // protected string $title;
    protected string $image;
    protected string $content;

    protected function grid(){
        return Grid::make(new LogSysMessage(), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            // $grid->column('uids');
            $grid->column('title', config('admin.sys_message.content_show') ? '标题' : '内容')->width('30%');
            $sys_user = config('admin.users');
            $grid->column('user_identity')->width('30%')->display(function() use($sys_user){
                if($this->uids == 0){
                    return "所有会员";
                }
                return implode(', ', (new User())->use_ids_get_identities_arr($this->uids));
            })->limit(30, '...');
            config('admin.sys_message.image_show') ? $grid->column('image')->image('', 40, 40) : '';
            config('admin.sys_message.content_show') ? '' : $grid->disableViewButton();
            $grid->column('created_at');

            $grid->disableEditButton();

            $grid->filter(function (Grid\Filter $filter) use($sys_user) {
                $filter->equal('id');
                $filter->equal('uid');
                $identity = $sys_user['user_identity'][0];
                $filter->like('user.' . $identity, '会员标识');
                $filter->like('title');
                $filter->between('created_at')->datetime();
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id){
        return Show::make($id, new LogSysMessage(), function (Show $show) {
            $show->field('id');
            $show->field('uid');
            $show->field('title');
            $show->field('image');
            $show->field('content');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(){
        return Form::make(new LogSysMessage(), function (Form $form) {
            $form->display('id');
            $form->multipleSelect('uids')->options("get/users")->help('不选择表示所有会员')->saving(function ($value) {
                return $value ? implode(',', $value) : '0';
            });
            if(config('admin.sys_message.image_show')){
                $form->image('image')->autoUpload()->uniqueName()->saveFullUrl()->required();
            }
            if(config('admin.sys_message.content_show')){
                $form->text('title')->required();
                $form->editor('content')->height('600')->disk(config('admin.upload_disk'))->required();
            }else{
                $form->text('title', '内容')->required();
                $form->hidden('content');
            }
            $form->saving(function (Form $form) {
                $form->content = $form->content ?? '';
            });

            // redis 处理
            $form->saved(function(Form $form, $result){
                if($form->isCreating()){
                    // 将数据保存到redis
                    $form->uids = array_filter($form->uids) ? implode(',', array_filter($form->uids)) : '0';
                    (new LogSysMessage())->save_uid_to_redis($form->getKey(), $form->uids);
                    (new LogSysMessage())->save_data_to_redis($form->getKey(), $form->title ?? '', $form->image ?? '', $form->content ?? '');
                }
                // 不能修改
            });
            $form->deleted(function(Form $form, $result){
                // 将redis中的数据删除
                $data = $form->model()->toArray()[0];
                (new LogSysMessage())->delete_data_form_redis($data['id'], $data['uids']);
            });

            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
            });
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
            $form->disableDeleteButton();
        });
    }
}
