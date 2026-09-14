<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestRecord;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * テスト基盤が動いていることを示すテスト。
 *
 * パッケージの挙動そのものは検証しない。
 */
class SmokeTest extends TestCase
{
    public function test_パッケージのルートが登録されている(): void
    {
        $this->assertTrue(Route::has('vein.signin'));
        $this->assertTrue(Route::has('vein.home'));
        $this->assertTrue(Route::has('vein.list'));
        $this->assertTrue(Route::has('vein.edit'));
    }

    public function test_設定がマージされている(): void
    {
        $this->assertSame('admin', config('vein.admin_uri'));
        $this->assertNull(config('vein.admin_guard'));
    }

    public function test_親に子レコードをぶら下げて保存できる(): void
    {
        $entry = TestEntry::create(['title' => 'テスト', 'body' => '本文']);
        $entry->records()->save(new TestRecord(['caption' => '1 枚目']));
        $entry->records()->save(new TestRecord(['caption' => '2 枚目']));

        $this->assertSame(2, $entry->records()->count());
        $this->assertSame(['1 枚目', '2 枚目'], $entry->records()->pluck('caption')->all());
    }

    public function test_entry_のデフォルトが_entry_helper_で埋まる(): void
    {
        $entry = new TestEntry;

        $this->assertSame('TestEntry', $entry->menuName());
        $this->assertSame(20, $entry->listItemPerPage());
        $this->assertSame([], $entry->searchFields());
    }
}
