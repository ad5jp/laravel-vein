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
Form オブジェクトを用いて定義します。  

```php
public function editFields(): array
{
    return [
        new InputText(
            key: 'product_name',
            label: '商品名',
        ),
        new SelectEnum(
            key: 'category',
            label: 'カテゴリ',
            enum: Category::class,
            colSize: 3,
        ),
        new SelectModel(
            key: 'maker_id',
            label: 'メーカー',
            model: Maker::class,
            modelLabel: 'maker_name',
            colSize: 3,
        ),
        new CheckboxesEnum(
            key: 'colors:color',
            label: 'カラーバリエーション',
            enum: Color::class,
        ),
        new TextArea(
            key: 'content',
            label: '開発内容',
        ),
    ];
}
```

配列と Form オブジェクトとを混在させることも可能です。  

**関連**  
[FormControl の種類](input-variations.md)  

# バリデーション

TODO

# レイアウト

## 入力欄の幅

Form オブジェクトの colSize で定義することができます（12＝画面幅100%）。  

## 横並び

Row オブジェクトを用いることで、複数の Form オブジェクトを横並びに配置できます。

```php
new Row([
    new InputText(
        key: 'last_name',
        label: '姓',
    ),
    new InputText(
        key: 'first_name',
        label: '名',
    ),
]),
```

## 複数入力欄の結合

Group オブジェクトを用いることで、複数の Form オブジェクトを結合して表示できます。

```php
new Group(
    label: '販売期間',
    colSize: 6,
    children: [
        new InputDate(key: 'sale_from'),
        '〜',
        new InputDate(key: 'sale_to'),
    ],
),
```
