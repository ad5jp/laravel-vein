<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\TestCase;

/**
 * 行の並びがそのまま並び順になること。
 *
 * 番号を手で入れる欄があると、足した行は空のまま送られて 0 になる。
 * 画面では一番下に足したのに、保存すると先頭へ飛ぶ。
 */
class RecordsSortableTest extends TestCase
{
    private function control(): Records
    {
        return (new Records(key: 'records', label: '子レコード'))->sortable();
    }

    private function entryWithRecords(string ...$captions): TestEntry
    {
        $entry = TestEntry::create(['title' => '親', 'body' => '本文']);

        foreach ($captions as $i => $caption) {
            $entry->records()->save(new TestRecord(['caption' => $caption, 'sort_order' => $i]));
        }

        return $entry->fresh();
    }

    public function test_足した行は一番下のまま(): void
    {
        $entry = $this->entryWithRecords('1 行目', '2 行目');
        $ids = $entry->records->pluck('id')->all();

        // 画面の一番下に足した行。並び順は送られてこない
        $request = ['records' => [
            ['id' => (string) $ids[0], 'caption' => '1 行目'],
            ['id' => (string) $ids[1], 'caption' => '2 行目'],
            ['id' => '', 'caption' => '3 行目'],
        ]];

        $this->control()->afterSave($entry, $request);

        $this->assertSame(
            ['1 行目', '2 行目', '3 行目'],
            $entry->fresh()->records()->orderBy('sort_order')->pluck('caption')->all(),
        );
    }

    public function test_入れ替えた順で保存される(): void
    {
        $entry = $this->entryWithRecords('1 行目', '2 行目', '3 行目');
        $ids = $entry->records->pluck('id')->all();

        // 3 行目を先頭へドラッグした状態。添字は描画時のまま届く
        $request = ['records' => [
            2 => ['id' => (string) $ids[2], 'caption' => '3 行目'],
            0 => ['id' => (string) $ids[0], 'caption' => '1 行目'],
            1 => ['id' => (string) $ids[1], 'caption' => '2 行目'],
        ]];

        $this->control()->afterSave($entry, $request);

        $this->assertSame(
            ['3 行目', '1 行目', '2 行目'],
            $entry->fresh()->records()->orderBy('sort_order')->pluck('caption')->all(),
        );
    }

    /** 手で入れた並び順より、画面に並んでいる順を正とする。 */
    public function test_送られてきた並び順は無視する(): void
    {
        $entry = $this->entryWithRecords('1 行目', '2 行目');
        $ids = $entry->records->pluck('id')->all();

        $request = ['records' => [
            ['id' => (string) $ids[0], 'caption' => '1 行目', 'sort_order' => '99'],
            ['id' => (string) $ids[1], 'caption' => '2 行目', 'sort_order' => '5'],
        ]];

        $this->control()->afterSave($entry, $request);

        $this->assertSame([0, 1], $entry->fresh()->records()->orderBy('sort_order')->pluck('sort_order')->all());
    }

    /** sortable を付けていない Records は、並び順に触らない。 */
    public function test_sortable_を付けなければ触らない(): void
    {
        $entry = $this->entryWithRecords('1 行目', '2 行目');
        $ids = $entry->records->pluck('id')->all();
        $entry->records()->whereKey($ids[0])->update(['sort_order' => 7]);

        $request = ['records' => [
            ['id' => (string) $ids[0], 'caption' => '1 行目'],
            ['id' => (string) $ids[1], 'caption' => '2 行目'],
        ]];

        (new Records(key: 'records', label: '子レコード'))->afterSave($entry, $request);

        $this->assertSame(7, $entry->fresh()->records()->whereKey($ids[0])->value('sort_order'));
    }

    public function test_つまむところと並べ替えの印が出る(): void
    {
        $entry = $this->entryWithRecords('1 行目');

        $html = $this->control()->render($entry);

        $this->assertStringContainsString('__records_sortable', $html);
        $this->assertStringContainsString('__records_handle', $html);
    }

    public function test_sortable_を付けなければ印は出ない(): void
    {
        $entry = $this->entryWithRecords('1 行目');

        $html = (new Records(key: 'records', label: '子レコード'))->render($entry);

        $this->assertStringNotContainsString('__records_sortable', $html);
        $this->assertStringNotContainsString('__records_handle', $html);
    }
}
