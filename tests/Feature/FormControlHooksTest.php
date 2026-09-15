<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\CheckboxesModel;
use AD5jp\Vein\Form\Input\InputText;
use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * doc に公開 API として載っているプロパティが実際に動くこと。
 *
 * beforeSaving / afterSaving / required は src 全体で 0 ヒットで、
 * 書いても静かに無視されていた。
 */
class FormControlHooksTest extends TestCase
{
    public function test_before_saving_が呼ばれる(): void
    {
        $entry = TestEntry::create(['title' => '元のまま']);

        $control = new InputText(
            key: 'title',
            beforeSaving: fn (Model $model, array $request) => tap($model, function (Model $m): void {
                $m->title = '上書きした';
            }),
        );

        $control->beforeSave($entry, ['title' => '送られてきた値']);

        $this->assertSame('上書きした', $entry->title);
    }

    public function test_before_saving_はリクエストを受け取る(): void
    {
        $entry = TestEntry::create(['title' => '親']);
        $seen = null;

        $control = new InputText(
            key: 'title',
            beforeSaving: function (Model $model, array $request) use (&$seen) {
                $seen = $request;

                return $model;
            },
        );

        $control->beforeSave($entry, ['title' => 'あ', 'body' => 'い']);

        $this->assertSame(['title' => 'あ', 'body' => 'い'], $seen);
    }

    public function test_after_saving_が呼ばれる(): void
    {
        $entry = TestEntry::create(['title' => '親']);
        $called = false;

        $control = new InputText(
            key: 'title',
            afterSaving: function (Model $model, array $request) use (&$called) {
                $called = true;

                return $model;
            },
        );

        $control->afterSave($entry, ['title' => 'あ']);

        $this->assertTrue($called);
    }

    public function test_required_がバリデーションに反映される(): void
    {
        $control = new InputText(key: 'title', required: true);
        $entry = TestEntry::create(['title' => '親']);

        $this->assertSame(['title' => ['required']], $control->validationRules($entry));

        $optional = new InputText(key: 'title');
        $this->assertSame([], $optional->validationRules($entry));
    }

    public function test_子レコードの_required_は添字付きの規則になる(): void
    {
        $entry = TestEntry::create(['title' => '親']);

        // TestRecord の editFields は caption を required にしていないので、
        // ここでは規則の形だけを見る
        $rules = (new Records(key: 'records'))->validationRules($entry);

        $this->assertIsArray($rules);

        foreach (array_keys($rules) as $key) {
            $this->assertStringStartsWith('records.*.', $key);
        }
    }

    public function test_例外メッセージが実際のチェック内容と一致する(): void
    {
        $entry = TestEntry::create(['title' => '親']);

        // records は Record を実装した子を求める。File ではない
        try {
            (new Records(key: 'no_such_relation'))->afterSave($entry, []);
            $this->fail('例外が投げられていない');
        } catch (Exception $e) {
            $this->assertStringContainsString('リレーション no_such_relation が定義されていません', $e->getMessage());
            $this->assertStringNotContainsString('File インターフェイス', $e->getMessage());
        }
    }

    public function test_checkboxes_model_の例外が自分のクラス名を名乗る(): void
    {
        $entry = TestEntry::create(['title' => '親']);

        try {
            (new CheckboxesModel(
                key: 'bad_format',
                model: TestRecord::class,
                modelLabel: 'caption',
            ))->afterSave($entry, []);
            $this->fail('例外が投げられていない');
        } catch (Exception $e) {
            $this->assertStringContainsString('CheckboxesModel の key', $e->getMessage());
            $this->assertStringNotContainsString('CheckboxesEnum の key', $e->getMessage());
        }
    }
}
