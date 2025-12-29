# Input の種類

| クラス名       | 略称      |
| ------------- | -------- |
| InputText     | text     |
| InputDate     | date     |
| TextArea      | textarea |
| SelectEnum    | -        |

# SelectEnum

セレクトボックスにより、Enum の値を選択させます。

## プロパティ

| プロパティ   | 型              | 概要                    |
| ---------- | --------------- | ---------------------- |
| $enum      | class-string    | 選択肢となるEnumのクラス名 |

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

## 説明
該当のモデルプロパティは、Enum キャストを定義してください。

デフォルトでは、Enum の name (case) が選択肢としてセレクトボックス上に表示されます。
当該 Enum に LabelledEnum インターフェースを実装することで、
選択肢の表示をカスタマイズできます。

```
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
