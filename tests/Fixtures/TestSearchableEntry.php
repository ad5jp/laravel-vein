<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

/**
 * 一覧に検索欄を持つ Entry。
 *
 * 編集では必須の欄を、検索にも出す。検索欄は空で送るのが普通なので、
 * 編集の規則を引いて「必須」の印を出してはいけない。
 */
class TestSearchableEntry extends TestEntry
{
    protected $table = 'test_entries';

    // EntryHelper は self::class から名前を作るので、継承元の TestEntry と同じ名前になる
    public function menuName(): string
    {
        return 'TestSearchableEntry';
    }

    public function editValidatorRules(): array
    {
        return [
            'title' => ['required'],
        ];
    }

    public function searchFields(): array
    {
        return [
            ['title', 'タイトル'],
        ];
    }
}
