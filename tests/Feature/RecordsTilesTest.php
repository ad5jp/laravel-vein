<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\TestCase;

/**
 * 画像を持つ子レコードをタイルで並べる。
 *
 * 縦に積むと 1 行が 500px ほどになり、一覧できず、並べ替えも運びきれない。
 */
class RecordsTilesTest extends TestCase
{
    private function entryWithRecords(string ...$captions): TestEntry
    {
        $entry = TestEntry::create(['title' => '親', 'body' => '本文']);

        foreach ($captions as $i => $caption) {
            $entry->records()->save(new TestRecord(['caption' => $caption, 'sort_order' => $i]));
        }

        return $entry->fresh();
    }

    /**
     * 「追加」で複製されるテンプレートを外した本体だけ。
     *
     * テンプレートも 1 行分の HTML なので、数えるときは分けないと 1 つ多くなる。
     */
    private function body(string $html): string
    {
        $at = strpos($html, '<script type="application/xml">');

        return $at === false ? $html : substr($html, 0, $at);
    }

    public function test_タイルとモーダルが出る(): void
    {
        $entry = $this->entryWithRecords('1 枚目', '2 枚目');

        $html = $this->body((new Records(key: 'records', label: '画像'))->tiles()->render($entry));

        $this->assertStringContainsString('__records_tiles', $html);
        $this->assertSame(2, substr_count($html, '__records_tile_face'));
        $this->assertSame(2, substr_count($html, '__records_tile_modal'));
    }

    public function test_tiles_を付けなければ従来の一覧(): void
    {
        $entry = $this->entryWithRecords('1 枚目');

        $html = (new Records(key: 'records', label: '画像'))->render($entry);

        $this->assertStringNotContainsString('__records_tiles', $html);
        $this->assertStringContainsString('__records_list_item', $html);
    }

    /**
     * モーダルの id は行ごとに変える。
     *
     * 重なると、どのタイルを押しても先頭の行が開く。
     */
    public function test_モーダルの_id_が行ごとに違う(): void
    {
        $entry = $this->entryWithRecords('1 枚目', '2 枚目', '3 枚目');

        $html = $this->body((new Records(key: 'records', label: '画像'))->tiles()->render($entry));

        preg_match_all('/id="(__rec_[A-Za-z0-9_]+)"/', $html, $matches);

        $this->assertCount(3, $matches[1]);
        $this->assertSame($matches[1], array_unique($matches[1]));
    }

    /** 入力欄はモーダルの中に入るが、name の付け方は一覧のときと変わらない。 */
    public function test_入力欄の_name_は一覧のときと同じ(): void
    {
        $entry = $this->entryWithRecords('1 枚目', '2 枚目');

        $tiles = (new Records(key: 'records', label: '画像'))->tiles()->render($entry);

        $this->assertStringContainsString('name="records[0][caption]"', $tiles);
        $this->assertStringContainsString('name="records[1][caption]"', $tiles);
    }

    /** タイルでも並べ替えの印は出る。 */
    public function test_タイルでも並べ替えられる(): void
    {
        $entry = $this->entryWithRecords('1 枚目');

        $html = (new Records(key: 'records', label: '画像'))->tiles()->sortable()->render($entry);

        $this->assertStringContainsString('__records_sortable', $html);
        // タイルはつまむところを持たない。1 枚が小さいので、そのまま掴む
        $this->assertStringNotContainsString('__records_handle', $html);
    }
}
