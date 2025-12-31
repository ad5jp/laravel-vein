<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\NodeManager;
use AD5jp\Vein\Node\Attributes\EditField;
use AD5jp\Vein\Node\Contracts\Entry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function init(string $node, mixed $id): View
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if ($model instanceof Entry) {
            return $this->initForEntry($model, $id, $node);
        }

        // TODO Taxonomy
        // TODO Page

        abort(404);
    }

    public function save(string $node, mixed $id, Request $request): RedirectResponse
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if ($model instanceof Entry) {
            return $this->saveEntry($model, $id, $node, $request);
        }

        // TODO Taxonomy
        // TODO Page

        abort(404);
    }

    public function delete(string $node, mixed $id): RedirectResponse
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if ($model instanceof Entry) {
            return $this->deleteEntry($model, $id, $node);
        }

        // TODO Taxonomy
        // TODO Page

        abort(404);
    }

    /**
     * @param Entry&Model $entry
     */
    private function initForEntry(Entry $model, mixed $id, string $node): View
    {
        assert($model instanceof Model);

        // 対象データ取得
        $entry = $model->findOrFail($id);

        // フィールド情報取得
        $manager = new InputManager();
        $editFields = $manager->parseEditField($model->editFields());

        return view('vein::entry-edit', [
            'node' => $node,
            'model' => $model,
            'entry' => $entry,
            'editFields' => $editFields,
        ]);
    }

    private function saveEntry(Entry $model, mixed $id, string $node, Request $request): RedirectResponse
    {
        assert($model instanceof Model);

        // TODO バリデーション

        // 対象データ取得
        $entry = $model->findOrFail($id);

        // フィールド情報取得
        $manager = new InputManager();
        $editFields = $manager->parseEditField($model->editFields());

        // 保存
        foreach ($editFields as $editField) {
            $entry = $editField->beforeSave($entry, $request);
        }

        $entry->save();

        foreach ($editFields as $editField) {
            $entry = $editField->afterSave($entry, $request);
        }

        return redirect()->route('vein.edit', ['node' => $node, 'id' => $entry->getKey()]);
    }

    private function deleteEntry(Entry $model, mixed $id, string $node): RedirectResponse
    {
        assert($model instanceof Model);

        // 対象データ取得
        $entry = $model->findOrFail($id);

        // 削除
        // TODO リレーションやファイルの削除
        $entry->delete();

        return redirect()->route('vein.list', ['node' => $node]);
    }
}
