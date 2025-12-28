<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Node\NodeManager;
use AD5jp\Vein\Node\Attributes\ListField;
use AD5jp\Vein\Node\Contracts\Entry;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

class ListController extends Controller
{
    public function init(string $node): View
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if ($model instanceof Entry) {
            return $this->initForEntry($model, $node);
        }

        // TODO Taxonomy
        // TODO Page

        abort(404);
    }

    /**
     * @param Entry&Model $entry
     */
    private function initForEntry(Entry $model, string $node): View
    {
        assert($model instanceof Model);

        // データ取得
        $builder = $model->newQuery();
        // TODO 検索
        // TODO ユーザソート
        $builder = $model->listOrderDefault($builder);
        $entries = $builder->paginate($model->listItemPerPage());

        // フィールド情報取得
        $listFields = ListField::parse($model->listFields());

        return view('vein::entry-list', [
            'node' => $node,
            'model' => $model,
            'listFields' => $listFields,
            'entries' => $entries
        ]);
    }


}
