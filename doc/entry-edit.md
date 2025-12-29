# Entry 登録・編集画面

# 基本

編集画面は、Model の editFields() メソッドにより定義します。  
  
基本的なテキスト項目であれば、以下のように配列形式で定義できます。

```php
public function editFields(): array
{
    return [
        ['title', 'タイトル', 'text'],
        ['publish_date', '公開日', 'date'],
        ['content', '内容', 'textarea'],
    ];
}
```

Enum や他の Model を参照する項目、ファイルアップロード項目、複数入力項目、  
保存時に加工が必要な項目、独自の入力欄を追加する場合などは、  
EditField オブジェクトを用いて定義します。  

```php
public function editFields(): array
{
    return [
        new EditField(
            key: 'title',
            label: 'タイトル',
            input: 'text',
        ),
        new EditField(
            key: 'publish_date',
            label: '公開日',
            input: 'date',
            default: today()->format('Y-m-d'),
            column_size: 4,
        ),
        new EditField(
            key: 'publish_date',
            label: 'カテゴリ',
            input: new SelectModel(
                model: Category::class,
                optionLabel: 'category_name',
                optionOrder: 'priority',
            ),
            column_size: 6,
        ),
    ];
}
```

配列と EditField オブジェクトとを混在させることも可能です。  

**関連**  
[EditField の詳細]  
[Input の種類]  
[独自 Input の作成]  

# バリデーション

TODO

# レイアウト

## 入力欄の幅

EditField オブジェクトの column_size で定義することができます（12＝画面幅100%）。  

## 複数入力欄の結合

TODO
