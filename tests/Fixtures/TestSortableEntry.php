<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Node\Contracts\Entry;
use AD5jp\Vein\Node\Contracts\Sortable;
use AD5jp\Vein\Node\Helpers\EntryHelper;
use AD5jp\Vein\Node\Helpers\SortableHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * 並べ替えできる Entry。グループ（ad5jp の「ページごとの実績」のページにあたる）ごとに並ぶ。
 */
class TestSortableEntry extends Model implements Entry, Sortable
{
    use EntryHelper;
    use SortableHelper;

    protected $table = 'test_sortables';

    protected $fillable = ['title', 'group', 'sort_order'];

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
            ['group', 'グループ'],
        ];
    }

    public function sortGroupColumn(): ?string
    {
        return 'group';
    }

    public function sortGroups(): array
    {
        return ['a' => 'グループ A', 'b' => 'グループ B'];
    }

    public function sortItemLabel(): string
    {
        return (string) $this->title;
    }
}
