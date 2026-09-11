<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\InputManager;
use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Contracts\Taxonomy;
use AD5jp\Vein\Node\NodeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class AddController extends Controller
{
    public function init(string $node): View
    {
        $manager = new NodeManager;
        $model = $manager->resolve($node);

        if ($model === null) {

        }

        if (! $model instanceof Entry) {
            abort(404);
        }

        // フィールド情報取得
        $manager = new InputManager;
        $editFields = $manager->parseEditField($model->editFields());

        return view('vein::entry-add', [
            'node' => $node,
            'model' => $model,
            'record' => $model->newInstance(),
            'editFields' => $editFields,
        ]);
    }

    public function save(string $node, Request $request): RedirectResponse|JsonResponse
    {
        $manager = new NodeManager;
        $model = $manager->resolve($node);

        if ($model === null) {
            abort(404);
        }

        if (! $model instanceof Entry && ! $model instanceof Taxonomy) {
            abort(404);
        }

        // バリデーション
        if ($model->editValidatorRules()) {
            Validator::make(
                $request->all(),
                $model->editValidatorRules(),
                $model->editValidatorMessages(),
                $model->editValidatorAttributes(),
            )->validate();
        }

        // フィールド情報取得
        $manager = new InputManager;
        $editFields = $manager->parseEditField($model->editFields());

        // 保存
        $record = DB::transaction(function () use ($model, $editFields, $request) {
            $record = $model->newInstance();

            foreach ($editFields as $editField) {
                $record = $editField->beforeSave($record, $request);
            }

            if ($model instanceof Taxonomy && $orderColumn = $model->orderColumn()) {
                $record->$orderColumn = ($model->max($orderColumn) ?? 0) + 1;
            }

            $record->save();

            foreach ($editFields as $editField) {
                $record = $editField->afterSave($record, $request);
            }

            return $record;
        });

        if ($model instanceof Entry) {
            return redirect()
                ->route('vein.edit', ['node' => $node, 'id' => $record->getKey()])
                ->with('message.success', '追加しました');
        }

        return response()->json(['message' => '登録しました', 'key' => $record->getKey()]);
    }
}
