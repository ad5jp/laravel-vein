<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\Group;
use AD5jp\Vein\Form\Input\InputText;
use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Form\Input\Row;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 必須の印は、検証の規則を正とする。
 *
 * 弾くのは規則、示すのは画面。欄の側にも「必須」を書く作りだと 2 か所に
 * 同じことを書くことになり、必ずずれる（実際に 12 件ずれていた）。
 * 画面が規則を見て印を出す、という結び付きをここで守る。
 */
class RequiredMarkTest extends TestCase
{
    private function entry(array $rules): Model
    {
        return new class($rules) extends TestEntry
        {
            public function __construct(private array $rules = [])
            {
                parent::__construct();
            }

            public function editValidatorRules(): array
            {
                return $this->rules;
            }

            public function records(): HasMany
            {
                return parent::records();
            }
        };
    }

    public function test_規則で必須なら印が出る(): void
    {
        $entry = $this->entry(['title' => ['required', 'max:10']]);
        $html = (new InputText(key: 'title', label: 'タイトル'))->render($entry);

        $this->assertStringContainsString('text-danger', $html, '印が出ていない');
        $this->assertStringContainsString('aria-required="true"', $html);
    }

    public function test_規則に無ければ印は出ない(): void
    {
        $entry = $this->entry(['title' => ['nullable', 'max:10']]);
        $html = (new InputText(key: 'title', label: 'タイトル'))->render($entry);

        $this->assertStringNotContainsString('text-danger', $html);
        $this->assertStringNotContainsString('aria-required', $html);
    }

    public function test_パイプ区切りの書き方でも拾う(): void
    {
        $entry = $this->entry(['title' => 'required|max:10']);
        $html = (new InputText(key: 'title', label: 'タイトル'))->render($entry);

        $this->assertStringContainsString('aria-required="true"', $html);
    }

    public function test_横に並べた欄も規則を見る(): void
    {
        $entry = $this->entry(['title' => ['required'], 'body' => ['nullable']]);
        $html = (new Row(children: [
            new InputText(key: 'title', label: 'タイトル'),
            new InputText(key: 'body', label: '本文'),
        ]))->render($entry);

        $this->assertSame(1, substr_count($html, 'aria-required="true"'), '必須の欄だけに出る');
    }

    /** 束ねた欄は renderColumn を通らないため、別の経路で拾う必要がある。 */
    public function test_束ねた欄の中も規則を見る(): void
    {
        $entry = $this->entry(['title' => ['required']]);
        $html = (new Group(label: 'URL', children: [
            '/works/',
            new InputText(key: 'title'),
        ]))->render($entry);

        $this->assertStringContainsString('aria-required="true"', $html, '中の欄に伝わっていない');
        $this->assertStringContainsString('text-danger', $html, '束ねた欄のラベルに印が出ていない');
    }

    /** 子レコードの規則は親に records.*.caption の形で集まっている。 */
    public function test_子レコードの欄も親の規則を見る(): void
    {
        $entry = $this->entry(['records.*.caption' => ['required', 'max:50']]);
        $entry->setAttribute('id', 1);
        $entry->exists = true;

        $html = (new Records(key: 'records', label: '画像'))->render($entry);

        $this->assertStringContainsString('aria-required="true"', $html, '子の欄に伝わっていない');
    }

    /** 描くたびに状態を書き換えると、次に別のモデルで描いたときに印が残る。 */
    public function test_同じ欄を使い回しても印は持ち越さない(): void
    {
        $field = new InputText(key: 'title', label: 'タイトル');

        $required = $field->render($this->entry(['title' => ['required']]));
        $optional = $field->render($this->entry(['title' => ['nullable']]));

        $this->assertStringContainsString('aria-required="true"', $required);
        $this->assertStringNotContainsString('aria-required', $optional, '前に描いた印が残っている');
    }

    /** 接頭辞だけを置く形では、束ねた欄の名前に印を出す。接頭辞には出さない。 */
    public function test_束ねた欄に名前があれば接頭辞には印を出さない(): void
    {
        $entry = $this->entry(['title' => ['required']]);
        $html = (new Group(label: 'URL に使う識別子', children: [
            '/works/',
            new InputText(key: 'title'),
        ]))->render($entry);

        $this->assertStringContainsString('URL に使う識別子<span class="text-danger', $html);
        $this->assertStringNotContainsString('/works/<span class="text-danger', $html);
    }

    public function test_子レコードの規則が無ければ印は出ない(): void
    {
        $entry = $this->entry(['records.*.caption' => ['nullable']]);
        $entry->setAttribute('id', 1);
        $entry->exists = true;

        $html = (new Records(key: 'records', label: '画像'))->render($entry);

        $this->assertStringNotContainsString('aria-required', $html);
    }
}
