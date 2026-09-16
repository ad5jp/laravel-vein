<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node;

use AD5jp\Vein\Node\Contracts\RootNode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;

class NodeManager
{
    public function resolve(string $node_name): ?Model
    {
        $namespaces = config('vein.model_namespaces');

        foreach ($namespaces as $namespace) {
            $model_classname = trim($namespace, '\\').'\\'.Str::pascal($node_name);

            if ($model = $this->instantiate($model_classname)) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Node になりうるクラスだけをインスタンス化する。
     *
     * class_exists() は abstract クラスにも enum にも true を返すため、
     * 判定の前に new すると Error になる。App\Models に 1 つ置かれただけで
     * 管理画面が落ちるので、Reflection で先に振り分ける。
     */
    public function instantiate(string $class_name): ?Model
    {
        if (! class_exists($class_name)) {
            return null;
        }

        $reflection = new ReflectionClass($class_name);

        // abstract / enum / interface / trait はここで落ちる
        if (! $reflection->isInstantiable()) {
            return null;
        }

        if (! $reflection->isSubclassOf(Model::class)) {
            return null;
        }

        if (! $reflection->implementsInterface(RootNode::class)) {
            return null;
        }

        // 引数の要るコンストラクタを持つモデルは扱えない
        $constructor = $reflection->getConstructor();
        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            return null;
        }

        /** @var Model */
        return $reflection->newInstance();
    }

    public function slug(Model $model): string
    {
        $model_name_parts = explode('\\', get_class($model));
        $model_base_name = $model_name_parts[array_key_last($model_name_parts)];

        return Str::snake($model_base_name);
    }
}
