<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\NodeManager;
use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Contracts\Page;
use AD5jp\Vein\Node\Contracts\Taxonomy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditController extends Controller
{
    public function init(string $node, mixed $id = null): View
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        // 更新対象レコード取得
        if ($model instanceof Entry) {
            $record = $model->findOrFail($id);
            $view = 'vein::entry-edit';
        } elseif ($model instanceof Page) {
            $record = $model->firstOrNew();
            $view = 'vein::page-edit';
        } else {
            abort(404);
        }

        // フィールド情報取得
        $manager = new InputManager();
        $editFields = $manager->parseEditField($model->editFields());

        return view($view, [
            'node' => $node,
            'model' => $model,
            'record' => $record,
            'editFields' => $editFields,
        ]);
    }

    public function save(Request $request, string $node, mixed $id = null): RedirectResponse|JsonResponse
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        // 更新対象レコード取得
        if ($model instanceof Entry || $model instanceof Taxonomy) {
            $record = $model->findOrFail($id);
        } elseif ($model instanceof Page) {
            $record = $model->firstOrNew();
        } else {
            abort(404);
        }

        // TODO バリデーション

        // フィールド情報取得
        $manager = new InputManager();
        $editFields = $manager->parseEditField($model->editFields());

        // 保存
        foreach ($editFields as $editField) {
            $record = $editField->beforeSave($record, $request);
        }

        $record->save();

        foreach ($editFields as $editField) {
            $record = $editField->afterSave($record, $request);
        }

        if ($model instanceof Entry || $model instanceof Page) {
            return redirect()->route('vein.edit', ['node' => $node, 'id' => $record->getKey()]);
        }

        return response()->json(['message' => '更新しました']);
    }

    public function delete(string $node, mixed $id): RedirectResponse|JsonResponse
    {
        $manager = new NodeManager();
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if (!$model instanceof Entry && !$model instanceof Taxonomy) {
            abort(404);
        }

        // 対象データ取得
        $record = $model->findOrFail($id);

        // 削除
        // TODO リレーションやファイルの削除
        $record->delete();

        if ($model instanceof Entry) {
            return redirect()->route('vein.list', ['node' => $node]);
        }

        return response()->json(['message' => '削除しました']);
    }
}
