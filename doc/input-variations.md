# FormControl の種類

| クラス名         | 略称      |
| --------------- | -------- |
| InputText       | text     |
| InputDate       | date     |
| InputNumber     | number   |
| TextArea        | textarea |
| SelectEnum      | -        |
| RadioEnum       | -        |
| SelectModel     | -        |
| RadioModel      | -        |
| CheckboxesEnum  | -        |
| CheckboxesModel | -        |
| FileUpload      | -        |

# 共通プロパティ

| プロパティ      | 必須 | 型      | 概要                    |
| ------------- | --- | ------- | ---------------------- |
| $key          | YES | string  | 対応するModelのプロパティ |
| $label        |  -  | string  | ラベル文字列             |
| $default      |  -  | mixed   | デフォルト値             |
| $colSize      |  -  | int     | 入力欄の幅 (画面幅＝12)   |
| $required     |  -  | bool    | 入力必須か。true にすると required のバリデーションが掛かる |
| $beforeSaving |  -  | Closure | 保存前に呼ばれる。`fn (Model $model, array $request) => $model` |
| $afterSaving  |  -  | Closure | 保存後に呼ばれる。引数と戻り値は $beforeSaving と同じ |
| $searching    |  -  | Closure | 検索条件を組み立てる。`fn (Builder $builder, mixed $value) => $builder` |

$beforeSaving / $afterSaving は Model を返してください。返さなかった場合は渡された Model が
そのまま使われます。

独自の入力要素を作るときは、beforeSave() / afterSave() ではなく
applyBeforeSave() / applyAfterSave() を実装してください。上記の Closure の呼び出しは
FormControl 側でまとめて行っています。

# InputNumber

数値入力欄 (`<input type="number">`) を表示させます。

## 追加プロパティ

| プロパティ | 必須 | 型                    | 概要             |
| -------- | --- | -------------------- | --------------- |
| $step    |     | int\|float\|string   | 入力できる刻み幅   |

**step** を省略すると、ブラウザ既定の `step="1"` が効き、**小数が入力できません**。
小数を扱う項目では明示してください。

```php
new InputNumber(
    key: 'development_months',
    label: '開発工数（人月）',
    step: 0.1,
),
```

刻み幅を問わない場合は `any` を指定します。

```php
new InputNumber(
    key: 'weight',
    label: '重量',
    step: 'any',
),
```

正の数でも `any` でもない値を渡すと、例外になります。

# SelectEnum

セレクトボックスにより、Enum の値を選択させます。

## 追加プロパティ

| プロパティ   | 必須 | 型              | 概要                    |
| ---------- | --- | --------------- | ---------------------- |
| $enum      | YES | class-string    | 選択肢となるEnumのクラス名 |

```php
new SelectEnum(
    key: 'category',
    label: 'カテゴリ',
    enum: Category::class,
    colSize: 3,
),
```

該当のモデルプロパティは、Enum キャストを定義してください。

デフォルトでは、Enum の name (case) が選択肢としてセレクトボックス上に表示されます。
当該 Enum に LabelledEnum インターフェースを実装することで、
選択肢の表示をカスタマイズできます。

```php
use AD5jp\Vein\Form\Contracts\LabelledEnum;

enum Category: int implements LabelledEnum
{
    case OUTER = 1;
    case SHIRT = 2;
    case SHOES = 3;

    public function label(): string
    {
        return match ($this) {
            self::OUTER => 'アウター',
            self::SHIRT => 'シャツ',
            self::SHOES => 'シューズ',
        };
    }
}
```

# RadioEnum

ラジオボタンにより、Enum の値を選択させます。

## プロパティ

SelectEnum と同様

# SelectModel

セレクトボックスにより、別の Model のレコードを選択させます。  
選択肢となる Model は、必ずしも Entry や Taxonomy である必要はありません。  

## 追加プロパティ

| プロパティ     | 必須 | 型              | 概要                                 |
| ------------ | --- | --------------- | ----------------------------------- |
| $model       | YES | class-string    | 選択肢となるModelのクラス名             |
| $modelLabel  | YES | string          | 選択肢に表示させるModelのプロパティ      |
| $modelOrder  |     | string|Closure  | 選択肢のソート順となるModelのプロパティ   |
| $modelWhere  |     | array|Closure   | 選択肢を特定のレコードに絞り込む場合の条件 |

```php
new SelectModel(
    key: 'maker_id',
    label: 'メーカー',
    model: Maker::class,
    modelLabel: 'maker_name',
    colSize: 3,
),
```

**key** には、外部キーとなるフィールドの値を定義します（リレーション名ではありません）。  
  
**modelLabel** には、アクセサ名も指定できます。  
複数のフィールドを結合したり、加工したりしたい場合はアクセサを使用してください。  
  
**modelOrder** を文字列で指定した場合、該当フィールドの昇順 (asc) となります。  
複数フィールドでソートしたい場合や、複雑なソートを行いたい場合は、  
Closure を指定してください。  
  
```php
new SelectModel(
    model: Maker::class,
    modelLabel: 'maker_name',
    modelOrder: fn (Builder $builder) => $builder->orderBy('priority', 'desc')->orderBy('id', 'desc'),
)
```
  
**modelWhere** を指定すると、特定のレコードだけに絞り込むことができます。  

```php
new SelectModel(
    model: Maker::class,
    modelLabel: 'maker_name',
    modelWhere: ['is_active', true],
)
```

```php
new SelectModel(
    model: Maker::class,
    modelLabel: 'maker_name',
    modelWhere: fn (Builder $builder) => $builder->whereIn('status', [1, 2]),
)
```

# RadioModel

ラジオボタンにより、別の Model のレコードを選択させます。  

## 追加プロパティ

SelectModel と同様

# CheckboxesEnum

チェックボックスにより、Enum の値を複数選択させます。

## 追加プロパティ

| プロパティ   | 必須 | 型              | 概要                    |
| ---------- | --- | --------------- | ---------------------- |
| $enum      | YES | class-string    | 選択肢となるEnumのクラス名 |

```php
new CheckboxesEnum(
    key: 'features:feature',
    label: '特徴',
    enum: Feature::class,
),
```

**key** には、HasMany リレーションのリレーション名と、  
Enum の値が格納されるリレーション先の Model のプロパティ名を連結してセットします。  

```php
class Product extends Model implements Entry
{
    public function features(): HasMany
    {
        $this->hasMany(ProductFeature::class);
    }
}

/**
 * @property Feature $feature
 */
class ProductFeature extends Model
{
    protected function casts(): array
    {
        return [
            'feature' => Feature::class,
        ];
    }    
}
```

選択肢となる Enum については、SelectEnum を参照してください。

# CheckboxesModel

チェックボックスにより、別の Model のレコードを複数選択させます。  
選択肢となる Model は、必ずしも Entry や Taxonomy である必要はありません。  

## 追加プロパティ

| プロパティ     | 必須 | 型              | 概要                                 |
| ------------ | --- | --------------- | ----------------------------------- |
| $model       | YES | class-string    | 選択肢となるModelのクラス名             |
| $modelLabel  | YES | string          | 選択肢に表示させるModelのプロパティ      |
| $modelOrder  |     | string|Closure  | 選択肢のソート順となるModelのプロパティ   |
| $modelWhere  |     | array|Closure   | 選択肢を特定のレコードに絞り込む場合の条件 |

```php
new CheckboxesModel(
    key: 'item_colors:color_id',
    label: 'カラー',
    model: Color::class,
    modelLabel: 'color_name',
    colSize: 3,
),
```

**key** には、HasMany リレーションのリレーション名と、  
選択したIDが格納されるリレーション先の Model のプロパティ名を連結してセットします。  

上記例の前提となる Model 構成は以下のとおりです。  

```php
/**
 * @property int $id
 */
class Item extends Model implements Entry
{
    public function item_colors(): HasMany
    {
        return $this->hasMany(ItemColor::class);
    }
}

/**
 * @property int $id
 * @property int $item_id
 * @property int $color_id
 */
class ItemColor extends Model
{

}

/**
 * @property int $id
 * @property string $color_name
 */
class Color extends Model
{

}
```

プロパティ $modelLabel, $modelOrder, $modelWhere については、  
SelectModel を参照してください。

# FileUpload
ファイルアップロードのUIを表示させます。

## 追加プロパティ

| プロパティ     | 必須 | 型              | 概要                                 |
| ------------ | --- | --------------- | ----------------------------------- |
| $disk        |     | string          | ファイルの保存先の Disk                |
| $directory   |     | string          | ファイルの保存先のディレクトリ           |

```php
new FileUpload(
    key: 'thumbnail',
    label: 'サムネイル'
)
```

**key** には、 BelongsTo リレーションのリレーション名をセットします。
リレーション先の Model には、File インターフェイスが実装されている必要があります。

**disk** および **directory** を省略した場合、config/vein.php に指定された
upload_disk および upload_path が使用されます。

# Records
HasMany の関係にあるサブテーブルの値を更新する複数行UIを表示させます。

## 追加プロパティ

なし

```php
new Records(
    key: 'reviews',
    label: 'カスタマーレビュー'
)
```

**key** には、 HasMany リレーションのリレーション名をセットします。
リレーション先の Model には、Record インターフェイスが実装されている必要があります。

サブテーブルの入力UIは、リレーション先 Model (Record) の editFields() で定義します。
