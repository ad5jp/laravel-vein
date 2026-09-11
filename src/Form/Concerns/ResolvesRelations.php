<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * key からリレーションを引き当てる。
 *
 * Records / FileUpload / CheckboxesModel / CheckboxesEnum がほぼ同じことをしていた。
 * 違うのは「期待するリレーション型」と「期待するインターフェイス」の 2 つだけなので、
 * ここに寄せている。
 */
trait ResolvesRelations
{
    /**
     * @param  class-string<Relation>  $expected_relation
     * @param  class-string|null  $expected_interface  関連先に実装を求めるインターフェイス
     */
    protected function resolveRelation(
        Model $model,
        string $key,
        string $expected_relation,
        ?string $expected_interface = null,
    ): Relation {
        foreach ([$key, Str::camel($key)] as $method_name) {
            if (! method_exists($model, $method_name)) {
                continue;
            }

            $relation = $model->$method_name();

            if (! $relation instanceof $expected_relation) {
                throw new Exception(sprintf(
                    'Model %s の %s() は %s リレーションではありません',
                    get_class($model),
                    $method_name,
                    class_basename($expected_relation),
                ));
            }

            if ($expected_interface !== null) {
                $related = $relation->getRelated();

                if (! $related instanceof $expected_interface) {
                    throw new Exception(sprintf(
                        'Model %s は %s インターフェイスを実装していません',
                        get_class($related),
                        class_basename($expected_interface),
                    ));
                }
            }

            return $relation;
        }

        throw new Exception(sprintf(
            'Model %s にリレーション %s が定義されていません',
            get_class($model),
            $key,
        ));
    }

    /**
     * relation_name:saving_field 形式の key を分解する。
     *
     * @return array{0:string, 1:string}
     */
    protected function parseRelationKey(Model $model, string $key): array
    {
        $segments = explode(':', $key);

        if (count($segments) !== 2) {
            throw new Exception(sprintf(
                '%s の key %s の形式が不正です（relation_name:saving_field）',
                class_basename(static::class),
                $key,
            ));
        }

        [$relation_name, $saving_field] = $segments;

        $this->resolveRelation($model, $relation_name, HasMany::class);

        return [$relation_name, $saving_field];
    }
}
