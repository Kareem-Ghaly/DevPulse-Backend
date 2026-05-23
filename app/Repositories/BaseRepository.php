<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    public function __construct(protected Model $model) {}

    public function find(int $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }
}
