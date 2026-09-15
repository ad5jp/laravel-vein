<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\TestCase;

/**
 * 子レコードが編集のたびに作り直されないこと。
 *
 * 症状が画面に出ないため、内容は同じに見えて主キーだけが変わる。
 * ファイルを持つ行では、参照されない孤児レコードが増え続ける。
 */
class RecordsTest extends TestCase
{
    private function control(): Records
    {
        return new Records(key: 'records', label: '子レコード');
    }

    private function entryWithRecords(string ...$captions): TestEntry
    {
        $entry = TestEntry::create(['title' => '親', 'body' => '本文']);

        foreach ($captions as $caption) {
            $entry->records()->save(new TestRecord(['caption' => $caption]));
        }

        return $entry->fresh();
    }

    public function test_同じ内容で保存しても子の主キーが変わらない(): void
    {
        $entry = $this->entryWithRecords('1 枚目', '2 枚目');
        $before = $entry->records->pluck('id')->all();

        // HTTP 経由の値は文字列で届く
        $request = ['records' => [
            ['id' => (string) $before[0], 'caption' => '1 枚目'],
            ['id' => (string) $before[1], 'caption' => '2 枚目'],
        ]];

        $this->control()->afterSave($entry, $request);

        $this->assertSame($before, $entry->fresh()->records->pluck('id')->all());
    }

    public function test_二回保存しても主キーが変わらない(): void
    {
        $entry = $this->entryWithRecords('1 枚目');
        $before = $entry->records->pluck('id')->all();

        foreach (['1 回目', '2 回目'] as $caption) {
            $request = ['records' => [['id' => (string) $before[0], 'caption' => $caption]]];
            $this->control()->afterSave($entry->fresh(), $request);
        }

        $entry = $entry->fresh();
        $this->assertSame($before, $entry->records->pluck('id')->all());
        $this->assertSame('2 回目', $entry->records->first()->caption);
    }

    public function test_行を足すと増える(): void
    {
        $entry = $this->entryWithRecords('1 枚目');
        $existing = $entry->records->first()->id;

        $request = ['records' => [
            ['id' => (string) $existing, 'caption' => '1 枚目'],
            ['id' => '', 'caption' => '2 枚目'],
        ]];

        $this->control()->afterSave($entry, $request);

        $entry = $entry->fresh();
        $this->assertCount(2, $entry->records);
        $this->assertSame($existing, $entry->records->first()->id);
    }

    public function test_送られてこなかった行だけが消える(): void
    {
        $entry = $this->entryWithRecords('1 枚目', '2 枚目', '3 枚目');
        $ids = $entry->records->pluck('id')->all();

        $request = ['records' => [
            ['id' => (string) $ids[0], 'caption' => '1 枚目'],
            ['id' => (string) $ids[2], 'caption' => '3 枚目'],
        ]];

        $this->control()->afterSave($entry, $request);

        $this->assertSame([$ids[0], $ids[2]], $entry->fresh()->records->pluck('id')->all());
    }

    public function test_行が空なら全部消える(): void
    {
        $entry = $this->entryWithRecords('1 枚目', '2 枚目');

        $this->control()->afterSave($entry, ['records' => []]);

        $this->assertCount(0, $entry->fresh()->records);
    }

    public function test_主キーの_hidden_が行の添字付きで出る(): void
    {
        $entry = $this->entryWithRecords('1 枚目');
        $id = $entry->records->first()->id;

        $html = $this->control()->render($entry);

        $this->assertStringContainsString(
            sprintf('name="records[0][id]" value="%s"', $id),
            $html,
        );
        $this->assertStringNotContainsString('name="id"', $html);
    }

    public function test_data_key_も行の添字付きになる(): void
    {
        $entry = $this->entryWithRecords('1 枚目');

        $html = $this->control()->render($entry);

        // Records の中の FileUpload は data-key から name を組み立てる
        $this->assertStringContainsString('data-key="records[0][file]"', $html);
        $this->assertStringNotContainsString('data-key="file"', $html);
    }

    public function test_配列で送る入力の名前が壊れない(): void
    {
        $entry = $this->entryWithRecords('1 枚目');

        $html = $this->control()->render($entry);

        $this->assertStringContainsString('name="records[0][tags][]"', $html);
        $this->assertStringNotContainsString('records[0][tags[]][]', $html);
    }
}
