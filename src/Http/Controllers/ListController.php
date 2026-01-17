<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\NodeManager;
use AD5jp\Vein\Node\Attributes\ListField;
use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Contracts\Taxonomy;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class ListController extends Controller
{
    public function init(string $node, Request $request): View
    {
        $manager = new NodeManager();
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
     * @param Entry&Model $entry
     */
    private function initForEntry(Entry $model, string $node, Request $request): View
    {
        assert($model instanceof Model);

        // 検索フォーム入力値
        $search = $model->newInstance();

        // 検索フォーム情報取得
        $manager = new InputManager();
        $searchFields = $manager->parseSearchField($model->searchFields());

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
            'entries' => $entries
        ]);
    }

    /**
     * @param Entry&Model $entry
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
        $manager = new InputManager();
        $editFields = $manager->parseEditField($model->editFields());

        return view('vein::taxonomy-list', [
            'node' => $node,
            'model' => $model,
            'editFields' => $editFields,
            'taxonomies' => $taxonomies
        ]);
    }
}
