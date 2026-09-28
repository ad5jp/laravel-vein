<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\Input\FormControl;
use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\Attributes\ListField;
use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Contracts\Taxonomy;
use AD5jp\Vein\Node\NodeManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ListController extends Controller
{
    public function init(string $node, Request $request): View
    {
        $manager = new NodeManager;
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if ($model instanceof Entry) {
            return $this->initForEntry($model, $node, $request);
        }

        if ($model instanceof Taxonomy) {
            return $this->initForTaxonomy($model, $node);
        }

        abort(404);
    }

    /**
     * @param  Entry&Model  $entry
     */
    private function initForEntry(Entry $model, string $node, Request $request): View
    {
        assert($model instanceof Model);

        // 検索フォーム入力値
        $search = $model->newInstance();

        // 検索フォーム情報取得
        $manager = new InputManager;
        $searchFields = $manager->parseSearchField($model->searchFields());

        // 絞り込みは空で当たり前。編集の検証規則を見て「必須」を出さないようにする
        foreach ($searchFields as $searchField) {
            if ($searchField instanceof FormControl) {
                $searchField->withScopedRules([]);
            }
        }

        // データ取得
        $builder = $model->newQuery();
        // 検索
        foreach ($searchFields as $searchField) {
            $builder = $searchField->searchQuery($builder, $request);
            $search = $searchField->afterSearch($search, $request);
        }
        // TODO ユーザソート
        $builder = $model->listOrderDefault($builder);
        $entries = $builder->paginate($model->listItemPerPage())->withQueryString();
        Paginator::useBootstrapFive();

        // フィールド情報取得
        $listFields = ListField::parse($model->listFields());

        return view('vein::entry-list', [
            'node' => $node,
            'model' => $model,
            'search' => $search,
            'listFields' => $listFields,
            'searchFields' => $searchFields,
            'entries' => $entries,
        ]);
    }

    /**
     * @param  Entry&Model  $entry
     */
    private function initForTaxonomy(Taxonomy $model, string $node): View
    {
        assert($model instanceof Model);

        // データ取得
        $builder = $model->newQuery();

        if ($model->orderColumn()) {
            $builder = $builder->orderBy($model->orderColumn(), 'asc');
        }

        $taxonomies = $builder->get();

        // フィールド情報取得
        $manager = new InputManager;
        $editFields = $manager->parseEditField($model->editFields());

        return view('vein::taxonomy-list', [
            'node' => $node,
            'model' => $model,
            'editFields' => $editFields,
            'taxonomies' => $taxonomies,
        ]);
    }

    public function sort(string $node, Request $request): JsonResponse
    {
        $manager = new NodeManager;
        $model = $manager->resolve($node);

        if (! $model instanceof Taxonomy) {
            abort(404);
        }

        if (! $orderColumn = $model->orderColumn()) {
            abort(404);
        }

        $ids = array_values(array_filter(explode(',', $request->input('ids', ''))));

        DB::transaction(function () use ($ids, $model, $orderColumn) {
            foreach ($ids as $index => $id) {
                $found = $model->find($id);
                if ($found) {
                    $found->$orderColumn = $index;
                    $found->save();
                }
            }
        });

        // 保存
        return response()->json(['message' => '並び順を変更しました']);
    }
}
