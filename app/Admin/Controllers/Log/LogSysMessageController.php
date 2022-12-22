<?php

namespace App\Admin\Controllers\Log;

use App\Admin\Repositories\Log\LogSysMessage;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Admin\Controllers\BaseController;
use App\Models\User\Users;

class LogSysMessageController extends BaseController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new LogSysMessage(), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('uid');
            $sys_user = config('admin.users');
            $grid->column('user_identity')->display(function() use($sys_user){
                if($this->uid == 0){
                    return "所有会员";
                }
                $identity = $sys_user['user_identity'][0];
                return $this->user->$identity;
            });
            $grid->column('title', config('admin.sys_message.content_show') ? '标题' : '内容')->width('40%');
            config('admin.sys_message.image_show') ? $grid->column('image')->image('', 40, 40) : '';
            config('admin.sys_message.content_show') ? '' : $grid->disableViewButton();
            $grid->column('created_at');
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
    protected function detail($id)
    {
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
    protected function form()
    {
        return Form::make(new LogSysMessage(), function (Form $form) {
            $form->display('id');
            $sys_user = config('admin.users');
            $identity = $sys_user['user_identity'][0];
            $form->select('uid')->options(Users::all()->pluck($identity, 'id'))->help('不选择表示所有会员');
            config('admin.sys_message.image_show') ? $form->image('image')->autoUpload()->uniqueName()->saveFullUrl()->required() : '';
            if(config('admin.sys_message.content_show')){
                $form->text('title')->required();
                $form->editor('content')->height('600')->disk(config('admin.upload_disk'))->required();
            }else{
                $form->text('title', '内容')->required();
                $form->hidden('content');
            }
            $form->saving(function (Form $form) {
                $form->content = $form->content ?? '';
                $form->uid = $form->uid ?? 0;
            });
            $form->saved(function(Form $form, $result){
                (new LogSysMessage())->del_cache_data($form->model()->uid);
            });
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
            $form->disableDeleteButton();
        });
    }
}
