<?php
namespace App\Api\Repositories\Log;

use App\Api\Repositories\BaseRepository;
use App\Models\Log\LogUserOperation as Model;

class LogUserOperationRepository extends BaseRepository{
    protected $eloquentClass = Model::class;
}