<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Form\Input\FileUpload;
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

    protected $fillable = ['test_entry_id', 'caption', 'test_file_id', 'tags', 'sort_order'];

    protected $casts = ['tags' => 'array'];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(TestEntry::class, 'test_entry_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(TestFile::class, 'test_file_id');
    }

    public function editFields(): array
    {
        return [
            ['caption', 'キャプション'],
            new FileUpload(key: 'file', label: '画像'),
            new ArrayInput(key: 'tags', label: 'タグ'),
        ];
    }
}
