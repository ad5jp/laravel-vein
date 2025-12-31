<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait InputHelper
{
    public function beforeSave(Model $model, string $key, Request $request): Model
    {
        $model->$key = $request->$key;
        return $model;
    }

    public function afterSave(Model $model, string $key, Request $request): Model
    {
        // DO NOTHING
        return $model;
    }
}
