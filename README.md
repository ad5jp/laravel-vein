# Laravel Vein  
  
# コンセプト  
Laravel を用いてWebサイトを開発する際に、モデルに所定のインターフェースを実装するだけで、  
自動的にコンテンツ管理画面を生成することができる。  
  
フロントサイトに対する機能は一切持たない。  
ルーティング、コントローラ、ビュー等、全て自前で実装する必要がある。  
逆に言えば、フロントサイトは何の制約もなく、自由に開発することができる。  
  
また、後述のいくつかのルールを除き、基本的にテーブル定義に関する制約はなく、  
自由にテーブルを定義することができる。  
  
  
# クイックスタート  
## インストール  
  
Laravel インストール後、  
  
```  
composer require ad5jp/laravel-vein  
php artisan vendor:publish  
```  
  
## 以下のようなモデルとテーブルを作成（サンプル）  
  
```php  
namespace App\Models;  
  
use AD5jp\Vein\Node\Contracts\Entry;  
use AD5jp\Vein\Node\Helpers\EntryHelper;  
use Illuminate\Database\Eloquent\Model;  
use Illuminate\Database\Eloquent\SoftDeletes;  
  
class Topic extends Model implements Entry  
{  
    use SoftDeletes, EntryHelper;  
  
    protected $table = "topics";  
  
    /**  
     * @var array<string, string>  
     */  
    protected $casts = [  
        'id' => 'integer',  
        'title' => 'string',  
        'publish_date' => 'date:Y-m-d',  
        'content' => 'string',  
        'created_at' => 'date:Y-m-d H:i:s',  
        'updated_at' => 'date:Y-m-d H:i:s',  
        'deleted_at' => 'date:Y-m-d H:i:s',  
    ];  
  
    /**  
     * @inheritDoc  
     */  
    public function listFields(): array  
    {  
        return [  
            ['id', 'ID'],  
            ['title', 'タイトル'],  
            ['publish_date', '公開日'],  
        ];  
    }  
  
    /**  
     * @inheritDoc  
     */  
    public function editFields(): array  
    {  
        return [  
            ['title', 'タイトル', 'text'],  
            ['publish_date', '公開日', 'date'],  
            ['content', '内容', 'textarea'],  
        ];  
    }  
}  
```  
  
## ログイン  
DataBaseSeeder 等を用いて User テーブルにレコードを作成。  
/admin にアクセス。  
  
  
# Vein の基礎  
## 基本概念  
### Entry  
同一形式で複数作成されるコンテンツ。  
典型的には、一覧ページと詳細ページを持つもの。  
お知らせ、商品案内、導入事例など。  
  
### Taxonomy  
エントリーを分類するために存在する、カテゴリやタグなどの概念。  
Entry と N対1（単一選択）か、N対N（複数選択）かを問わない。  
  
### Page  
サイト内で常に1つだけ存在するコンテンツ。  
会社案内やプライバシーポリシーなど。  
  
### Record  
Entryまたは Page と1対Nのサブデータとなるもの。  
独立したページにはならない。  
例えば、商品Entryにおけるカラーバリエーション（名称、品番、価格）や、会社案内Pageにおける沿革（年、月、概要、説明文）など。  
  
### File  
上記いずれかに紐づく、画像などの添付ファイル。  
Entry 等と1:1 か、1:N かを問わない。  
  
### Node  
上記全てを包括する概念。  
基本的に全てのNodeはModelで表現される。  
（関わる全てのModelがNodeなわけではない）  
  
## データベース設計のルール  
ファイルアップロード (File)  
アップロードファイルは、種類ごとに独立したモデルとして扱う必要がある。  
Entry, Taxonomy, Record, Page のいずれかに紐づけられる。1:1 か、1:N かを問わない。  
  
独立ページ (Page)  
例えばトップページや会社概要のような、恒久的に1ページしか存在しないようなページを管理画面から更新可能にしたい場合、ページごとに専用のモデルを用意する必要がある。  
言い換えると、常に1レコードしか存在しないテープルを作る必要がある。  
  
  
# 初期設定  
## 管理画面ログイン用 Auth の設定  
コンテンツ管理画面へのログインには、  
デフォルトでは config/auth.php に設定されたデフォルトガードを使用します。  
それ以外のガードを使用する場合は、config/auth.php にガードを定義の上、  
config/vein.php の **admin_guard** に設定してください。  
どちらの場合でも、SessionGuard を使用したガードである必要があります。  
  
## 管理画面URLの変更  
config/vein.php の **admin_uri** に設定してください。  
  
  
# 開発予定  
## ログイン関連  
- ログイン画面実装  
- ユーザ管理画面  
  
## Inputの追加  
- SelectEnum  
- SelectModel  
- RadioEnum  
- RadioModel  
- SelectMultipleEnum  
- SelectMultipleModel  
- CheckboxesEnum  
- CheckboxesModel  
- Wysiwyg  
- File  
- FileMultiple  
- Records  
  
## Entry以外のNodeの対応  
- Taxonomy  
- Page