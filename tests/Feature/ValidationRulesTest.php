<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;

/**
 * 編集のバリデーションが、いま編集しているレコードを見ること。
 *
 * 空のモデルで呼ばれると getKey() が null になり、自分自身を除外する
 * unique ルール（Rule::unique()->ignore($this->getKey())）が成立しない。
 * 症状は「何も変えずに保存すると重複エラーで弾かれる」形で出る。
 */
class ValidationRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestEntry::$validatorKeys = [];
    }

    public function test_編集では編集中のレコードで呼ばれる(): void
    {
        $entry = TestEntry::create(['title' => '親', 'body' => '本文']);

        $this->actingAs(TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]))->post('/'.config('vein.admin_uri').'/test-entry/'.$entry->getKey(), [
            'title' => '親',
            'body' => '本文',
        ]);

        $this->assertSame(
            [$entry->getKey()],
            TestEntry::$validatorKeys,
            '編集では、いま編集しているレコードの主キーが取れなければならない',
        );
    }

    public function test_新規では主キーがない(): void
    {
        $this->actingAs(TestUser::create([
            'name' => '管理者',
            'email' => 'admin2@example.com',
            'password' => 'password',
        ]))->post('/'.config('vein.admin_uri').'/test-entry/add', [
            'title' => '新しい親',
            'body' => '本文',
        ]);

        $this->assertSame([null], TestEntry::$validatorKeys, '新規では主キーが無いのが正しい');
    }
}
