# FormControl の種類

| クラス名        | 略称      |
| -------------- | -------- |
| InputText      | text     |
| InputDate      | date     |
| TextArea       | textarea |
| SelectEnum     | -        |
| RadioEnum      | -        |
| SelectModel    | -        |
| RadioModel     | -        |
| CheckboxesEnum | -        |
| FileUpload     | -        |

# 共通プロパティ

| プロパティ      | 必須 | 型      | 概要                    |
| ------------- | --- | ------- | ---------------------- |
| $key          | YES | string  | 対応するModelのプロパティ |
| $label        |  -  | string  | ラベル文字列             |
| $default      |  -  | mixed   | デフォルト値             |
| $colSize      |  -  | int     | 入力欄の幅 (画面幅＝12)   |
| $required     |  -  | bool    | 入力必須か               |
| $beforeSaving |  -  | Closure |                        |
| $afterSaving  |  -  | Closure |                        |
| $searching    |  -  | Closure |                        |

# SelectEnum

セレクトボックスにより、Enum の値を選択させます。

## プロパティ

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

## プロパティ

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

## プロパティ

SelectModel と同様

# CheckboxesEnum

チェックボックスにより、Enum の値を複数選択させます。

## プロパティ

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

# FileUpload
ファイルアップロードのUIを表示させます。

## プロパティ

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
