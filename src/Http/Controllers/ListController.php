<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\Input\FormControl;
use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\Attributes\ListField;
use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Contracts\Sortable;
use AD5jp\Vein\Node\Contracts\Taxonomy;
use AD5jp\Vein\Node\NodeManager;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
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
        // 並べ替えできるノードは、公開側と同じ並び順で並べる。新しい順だと、
        // 並べ替えた結果が一覧から読み取れない
        $builder = $model instanceof Sortable
            ? $this->orderedBySort($builder, $model)
            : $model->listOrderDefault($builder);
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

    /**
     * 並べ替えの画面。全件をグループごとに並べる。
     */
    public function order(string $node): View
    {
        $model = (new NodeManager)->resolve($node);

        if (! $model instanceof Entry || ! $model instanceof Sortable) {
            abort(404);
        }

        $entries = $this->orderedBySort($model->newQuery(), $model)->get();

        $groupColumn = $model->sortGroupColumn();
        $groups = [];

        if ($groupColumn === null) {
            $groups[''] = ['label' => null, 'entries' => $entries];
        } else {
            // 宣言された順にグループを出す。行が 0 件のグループも出す（どこに何も無いかが分かる）
            foreach ($model->sortGroups() as $value => $label) {
                $groups[(string) $value] = ['label' => $label, 'entries' => collect()];
            }

            foreach ($entries as $entry) {
                $value = $this->groupValue($entry, $groupColumn);
                $groups[$value] ??= ['label' => $value, 'entries' => collect()];
                $groups[$value]['entries']->push($entry);
            }
        }

        return view('vein::entry-order', [
            'node' => $node,
            'model' => $model,
            'groups' => $groups,
        ]);
    }

    public function sort(string $node, Request $request): JsonResponse
    {
        $manager = new NodeManager;
        $model = $manager->resolve($node);

        $ids = array_values(array_filter(explode(',', $request->input('ids', ''))));

        if ($model instanceof Entry && $model instanceof Sortable) {
            return $this->sortEntries($model, $ids);
        }

        if (! $model instanceof Taxonomy) {
            abort(404);
        }

        if (! $orderColumn = $model->orderColumn()) {
            abort(404);
        }

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

    /**
     * @param  Entry&Sortable&Model  $model
     * @param  list<string>  $ids
     */
    private function sortEntries(Entry&Sortable $model, array $ids): JsonResponse
    {
        assert($model instanceof Model);

        $column = $model->sortColumn();
        $groupColumn = $model->sortGroupColumn();
        $rows = $model->newQuery()->whereKey($ids)->get()->keyBy(fn (Model $row) => (string) $row->getKey());

        if ($rows->count() !== count(array_unique($ids))) {
            return response()->json(['message' => '並べ替える行が見つかりません。画面を読み込み直してください。'], 422);
        }

        // 並べ替えはグループの中だけ。別のグループの行を混ぜて送ると、そのグループの順番まで書き換わる
        if ($groupColumn !== null && $rows->map(fn (Model $row) => $this->groupValue($row, $groupColumn))->unique()->count() > 1) {
            return response()->json(['message' => '別のグループの行が混ざっています。画面を読み込み直してください。'], 422);
        }

        DB::transaction(function () use ($ids, $rows, $column): void {
            foreach ($ids as $index => $id) {
                $row = $rows[(string) $id];
                $row->$column = $index;
                $row->save();
            }
        });

        return response()->json(['message' => '並び順を変更しました']);
    }

    /**
     * グループ → 並び順 → 主キーの順。並び順が同じ行も、毎回同じ順に出す。
     */
    private function orderedBySort(Builder $builder, Sortable $model): Builder
    {
        assert($model instanceof Model);

        if ($groupColumn = $model->sortGroupColumn()) {
            $builder->orderBy($groupColumn);
        }

        return $builder->orderBy($model->sortColumn())->orderBy($model->getKeyName());
    }

    private function groupValue(Model $row, string $column): string
    {
        $value = $row->getAttribute($column);

        // enum にキャストした列は、その値で見分ける
        return (string) ($value instanceof BackedEnum ? $value->value : $value);
    }
}
