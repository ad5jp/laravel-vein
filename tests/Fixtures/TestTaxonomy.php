<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Node\Contracts\Taxonomy;
use AD5jp\Vein\Node\Helpers\TaxonomyHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * テスト用の分類。一覧の画面の中で行を追加・編集・並べ替える形の最小形。
 *
 * 欄を 2 つ持つのは、行ごとの id の分け方を、欄をまたいで確かめられるようにするため。
 */
class TestTaxonomy extends Model implements Taxonomy
{
    use TaxonomyHelper;

    protected $table = 'test_taxonomies';

    protected $fillable = ['code', 'name', 'sort_order'];

    public function orderColumn(): ?string
    {
        return 'sort_order';
    }

    public function editFields(): array
    {
        return [
            ['code', '識別子'],
            ['name', '名前'],
        ];
    }

    public function editValidatorRules(): array
    {
        return [
            'name' => ['required'],
        ];
    }
}
