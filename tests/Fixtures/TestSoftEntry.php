<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Form\Input\Records;
use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Helpers\EntryHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 論理削除する Entry。削除時の挙動が物理削除と分かれることを確かめるために置いている。
 *
 * 子は TestEntry と同じテーブルを使う。テストでは親を 1 件ずつしか作らないため
 * 取り違えは起きない。
 */
class TestSoftEntry extends Model implements Entry
{
    use EntryHelper;
    use SoftDeletes;

    protected $table = 'test_soft_entries';

    protected $fillable = ['title'];

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
            new Records(key: 'records', label: '子レコード'),
        ];
    }
}
