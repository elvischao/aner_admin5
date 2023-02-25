<?php
namespace App\Api\Services\Trigonal;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;

# composer require alibabacloud/dysmsapi-20170525 2.0.23
class AliyunSmsService{

    protected $accessKeyId;
    protected $accessKeySecret;
    protected $config;

    public function __construct(){
        $this->accessKeyId = env('ALIYUN_AK');
        $this->accessKeySecret = env("ALIYUN_AS");
        $this->config = new Config([
            "accessKeyId" => $this->accessKeyId,
            "accessKeySecret" => $this->accessKeySecret
        ]);
    }

    public function aliyun_sms(string $phone, int $sms_code){
        $this->config->endpoint = "dysmsapi.aliyuncs.com";
        $client = new Dysmsapi($this->config);
        $sendSmsRequest = new SendSmsRequest([
            "phoneNumbers" => $phone,
            "signName" => "成都亿喆鑫科技有限公司",
            "templateCode" => "SMS_270345031",
            "templateParam" => "{\"code\": \"" . $sms_code . "\"}"
        ]);
        $runtime = new RuntimeOptions([]);
        try{
            $client->sendSmsWithOptions($sendSmsRequest, $runtime);
        }catch(Exception $error){
            if(!($error instanceof TeaError)){
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            throwBusinessException($error->message);
        }
    }
}