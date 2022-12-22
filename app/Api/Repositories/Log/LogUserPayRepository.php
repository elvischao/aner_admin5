<?php
namespace App\Api\Repositories\Log;

use App\Api\Repositories\BaseRepository;
use App\Models\Log\LogUserPay as Model;

class LogUserPayRepository extends BaseRepository{
    protected $eloquentClass = Model::class;
}