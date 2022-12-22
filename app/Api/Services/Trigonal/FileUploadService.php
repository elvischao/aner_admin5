<?php
namespace App\Api\Services\Trigonal;

use zgldh\QiniuStorage\QiniuStorage;

class FileUploadService{
    protected $allowed_ext = ["png", "jpg", "gif", 'jpeg', 'mp4', 'mp3'];

    /**
     * 上传图片
     *
     * @param resource $file 文件资源对象
     * @return void
     */
    public function upload_file($file){
        $this->disk = config('filesystems.default');
        $this->directory = 'uploads/images/' . date('Y-m', time()) . '/';
        $upload_function = 'upload2' . $this->disk;
        return $this->$upload_function($file);
    }

    /**
     * 将图片上传到本地
     *
     * @param resource $file 文件资源对象
     * @return void
     */
    private function upload2local($file){
        $file_data = $this->rename($file);
        $file->move($this->directory, $file_data['file_name']);
        return $file_data['full_path_file_name'];
    }

    /**
     * 将图片上传到七牛云
     *
     * @param resource $file 文件资源对象
     * @return void
     */
    private function upload2qiniu($file){
        $disk = QiniuStorage::disk('qiniu');
        $file_data = $this->rename($file);
        $bool = $disk->put($file_data['directory_file_name'], file_get_contents($file->getRealPath()));
        if($bool){
            $path = $disk->downloadUrl($file_data['directory_file_name']);
            return $path;
        }
        return error('上传失败');
    }

    /**
     * 重命名文件并返回保存路径和本地完整访问路径
     *
     * @param resource $file
     * @return void
     */
    private function rename($file){
        if(!in_array($file->getClientOriginalExtension(), $this->allowed_ext)){
            throwBusinessException('不支持上传此类型文件');
        }
        $file_name = md5($file->getClientOriginalName().time().rand()).'.'.$file->getClientOriginalExtension();
        $directory_file_name = $this->directory . $file_name;
        $full_path_file_name = config('app.url') . '/' . $directory_file_name;
        return [
            'file_name'=> $file_name,
            'directory_file_name'=> $directory_file_name,
            'full_path_file_name'=> $full_path_file_name
        ];
    }
}