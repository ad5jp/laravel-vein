<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Tests\Fixtures\TestTaxonomy;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Hash;

/**
 * 分類（Taxonomy）の一覧の画面。
 *
 * 1 行 = 1 フォームで、同じ欄を行の数だけ描く。ラベルと欄を id で結ぶので、
 * 行ごとに id を分けないと、どの行のラベルを押しても 1 行目の欄に入る。
 *
 * 追加後の読み直し・未保存の確認・保存の結果の表示は JS の挙動なので、ここでは確かめない
 * （ブラウザで確かめる）。
 */
class TaxonomyListTest extends TestCase
{
    private function admin(): TestUser
    {
        return TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);
    }

    private function adminUri(): string
    {
        return '/'.config('vein.admin_uri');
    }

    private function listHtml(): string
    {
        foreach (['一', '二', '三'] as $i => $name) {
            TestTaxonomy::create(['code' => "c{$i}", 'name' => $name, 'sort_order' => $i]);
        }

        return $this->actingAs($this->admin())
            ->get($this->adminUri().'/test_taxonomy')
            ->assertOk()
            ->getContent();
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($dom);
    }

    public function test_同じidが2つ以上ない(): void
    {
        $ids = [];
        foreach ($this->xpath($this->listHtml())->query('//*[@id]') as $node) {
            assert($node instanceof DOMElement);
            $ids[] = $node->getAttribute('id');
        }

        $duplicates = array_keys(array_filter(array_count_values($ids), fn (int $n) => $n > 1));

        $this->assertSame([], $duplicates, '同じ id が複数ある');
    }

    public function test_ラベルは同じ行の欄を指す(): void
    {
        $xpath = $this->xpath($this->listHtml());
        $forms = $xpath->query('//form[contains(@class, "__edit_form") or contains(@class, "__add_form")]');

        // 既存の 3 行と、追加用の 1 行
        $this->assertCount(4, $forms);

        foreach ($forms as $form) {
            $labels = $xpath->query('.//label[@for]', $form);
            $this->assertGreaterThan(0, $labels->length, 'ラベルが欄と結ばれていない');

            foreach ($labels as $label) {
                assert($label instanceof DOMElement);
                $for = $label->getAttribute('for');
                $this->assertSame(
                    1,
                    $xpath->query(sprintf('.//*[@id="%s"]', $for), $form)->length,
                    "ラベル（for={$for}）が同じ行の欄を指していない",
                );
            }
        }
    }

    public function test_ボタンは日本語(): void
    {
        $html = $this->listHtml();

        foreach (['保存', '削除', '追加'] as $label) {
            $this->assertStringContainsString(">{$label}</button>", $html);
        }

        foreach (['EDIT', 'DELETE', 'ADD'] as $label) {
            $this->assertStringNotContainsString(">{$label}<", $html);
        }
    }

    public function test_検索欄に必須の印は出ない(): void
    {
        $html = $this->actingAs($this->admin())
            ->get($this->adminUri().'/test_searchable_entry')
            ->assertOk()
            ->getContent();

        // 検索欄は空で送るのが普通。編集の規則（title は必須）を引いてはいけない
        $this->assertStringNotContainsString('aria-required', $html);
    }

    public function test_追加と保存と並べ替えは今までどおりJSONで返る(): void
    {
        $admin = $this->admin();

        $added = $this->actingAs($admin)
            ->postJson($this->adminUri().'/test_taxonomy/add', ['code' => 'x', 'name' => '追加した'])
            ->assertOk()
            ->json();
        $this->assertArrayHasKey('key', $added);

        $other = TestTaxonomy::create(['code' => 'y', 'name' => 'もう 1 つ', 'sort_order' => 9]);

        $this->actingAs($admin)
            ->postJson(sprintf('%s/test_taxonomy/%s', $this->adminUri(), $added['key']), ['code' => 'x', 'name' => '直した'])
            ->assertOk()
            ->assertJsonStructure(['message']);
        $this->assertSame('直した', TestTaxonomy::find($added['key'])->name);

        $this->actingAs($admin)
            ->postJson($this->adminUri().'/test_taxonomy/sort', ['ids' => "{$other->id},{$added['key']}"])
            ->assertOk();
        $this->assertSame(0, (int) $other->fresh()->sort_order);
        $this->assertSame(1, (int) TestTaxonomy::find($added['key'])->sort_order);
    }

    public function test_必須の欄を空で保存すると欄ごとの理由が返る(): void
    {
        $taxonomy = TestTaxonomy::create(['code' => 'z', 'name' => '名前あり']);

        // 画面は errors を使って、弾かれた行の欄を赤くする
        $this->actingAs($this->admin())
            ->postJson(sprintf('%s/test_taxonomy/%s', $this->adminUri(), $taxonomy->id), ['code' => 'z', 'name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
