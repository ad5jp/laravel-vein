<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\NodeManager;
use AD5jp\Vein\Node\Contracts\Entry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddController extends Controller
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

    public function save(string $node, Request $request): RedirectResponse
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if ($model instanceof Entry) {
            return $this->saveEntry($model, $node, $request);
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

        // フィールド情報取得
        $manager = new InputManager();
        $editFields = $manager->parseEditField($model->editFields());

        return view('vein::entry-add', [
            'node' => $node,
            'model' => $model,
            'editFields' => $editFields,
        ]);
    }

    private function saveEntry(Entry $model, string $node, Request $request): RedirectResponse
    {
        assert($model instanceof Model);

        // TODO バリデーション

        // フィールド情報取得
        $manager = new InputManager();
        $editFields = $manager->parseEditField($model->editFields());

        // 保存
        $entry = $model->newInstance();

        foreach ($editFields as $editField) {
            $entry = $editField->beforeSave($entry, $request);
        }

        $entry->save();

        foreach ($editFields as $editField) {
            $entry = $editField->afterSave($entry, $request);
        }

        return redirect()->route('vein.edit', ['node' => $node, 'id' => $entry->getKey()]);
    }


}
