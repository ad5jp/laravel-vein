<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Node\Contracts\Record;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TestEntry にぶら下がる子レコード。
 *
 * Records フォームコントロールが編集対象にするのはこのモデル。
 */
class TestRecord extends Model implements Record
{
    protected $table = 'test_records';

    protected $fillable = ['test_entry_id', 'caption'];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(TestEntry::class, 'test_entry_id');
    }

    public function editFields(): array
    {
        return [
            ['caption', 'キャプション'],
        ];
    }
}
