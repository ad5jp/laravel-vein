<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\NodeManager;
use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Contracts\Taxonomy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

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

    public function save(string $node, Request $request): RedirectResponse|JsonResponse
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if (!$model instanceof Entry && !$model instanceof Taxonomy) {
            abort(404);
        }

        // TODO バリデーション

        // フィールド情報取得
        $manager = new InputManager();
        $editFields = $manager->parseEditField($model->editFields());

        // 保存
        $record = $model->newInstance();

        foreach ($editFields as $editField) {
            $record = $editField->beforeSave($record, $request);
        }

        $record->save();

        foreach ($editFields as $editField) {
            $record = $editField->afterSave($record, $request);
        }

        if ($model instanceof Entry) {
            return redirect()->route('vein.edit', ['node' => $node, 'id' => $record->getKey()]);
        }

        return response()->json(['message' => '登録しました', 'key' => $record->getKey()]);
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
}
