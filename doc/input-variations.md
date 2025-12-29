# Input の種類

| クラス名       | 略称      |
| ------------- | -------- |
| InputText     | text     |
| InputDate     | date     |
| TextArea      | textarea |
| SelectEnum    | -        |
| SelectModel   | -        |

# SelectEnum

セレクトボックスにより、Enum の値を選択させます。

## プロパティ

| プロパティ   | 必須 | 型              | 概要                    |
| ---------- | --- | --------------- | ---------------------- |
| $enum      | YES | class-string    | 選択肢となるEnumのクラス名 |

```php
new EditField(
    key: 'color',
    label: '色',
    input: new SelectEnum(
        enum: Color::class,
    ),
    column_size: 3,
),
```

該当のモデルプロパティは、Enum キャストを定義してください。

デフォルトでは、Enum の name (case) が選択肢としてセレクトボックス上に表示されます。
当該 Enum に LabelledEnum インターフェースを実装することで、
選択肢の表示をカスタマイズできます。

```php
use AD5jp\Vein\Form\Contracts\LabelledEnum;

enum Color: int implements LabelledEnum
{
    case RED = 1;
    case BLUE = 2;
    case YELLOW = 3;

    public function label(): string
    {
        return match ($this) {
            self::RED => '赤',
            self::BLUE => '青',
            self::YELLOW => '黄色',
        };
    }
}
```

# SelectEnum

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
new EditField(
    key: 'maker_id',
    label: 'メーカー',
    input: new SelectModel(
        model: Maker::class,
        modelLabel: 'maker_name',
        modelOrder: 'priority',
    ),
    column_size: 3,
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

