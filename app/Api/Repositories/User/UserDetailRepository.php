<?php
namespace App\Api\Repositories\User;

use App\Api\Repositories\BaseRepository;
use App\Models\User\UserDetail as Model;

class UserDetailRepository extends BaseRepository{
    protected $eloquentClass = Model::class;
}