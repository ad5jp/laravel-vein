<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Helpers\EntryHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * テスト用の Entry。件数が増えるコンテンツの最小形。
 *
 * 子レコードを 1 対多で持つのは、Records フォームコントロールの挙動を
 * 確かめられるようにするため。
 */
class TestEntry extends Model implements Entry
{
    use EntryHelper;

    protected $table = 'test_entries';

    protected $fillable = ['title', 'body'];

    public function records(): HasMany
    {
        return $this->hasMany(TestRecord::class, 'test_entry_id');
    }

    public function listFields(): array
    {
        return [
            ['title', 'タイトル'],
        ];
    }

    public function editFields(): array
    {
        return [
            ['title', 'タイトル'],
            ['body', '本文', 'textarea'],
            new Records(key: 'records', label: '子レコード'),
        ];
    }
}
