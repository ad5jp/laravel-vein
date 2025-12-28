<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NodeManager
{
    public function resolve(string $node_name): ?Model
    {
        $namespaces = config('vein.model_namespaces');

        foreach ($namespaces as $namespace) {
            $model_classname = $namespace . '\\' . Str::pascal($node_name);
            if (class_exists($model_classname)) {
                return new $model_classname();
            }
        }

        return null;
    }

    public function slug(Model $model): string
    {
        $model_name_parts = explode('\\', get_class($model));
        $model_base_name = $model_name_parts[array_key_last($model_name_parts)];

        return Str::snake($model_base_name);
    }
}
