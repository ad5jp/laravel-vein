# Laravel Vein  
  
## Laravel Vein とは
  
Laravel Vein は、Laravel アプリケーション向けに設計された  
**開発者志向のコンテンツ管理システム（CMS）**です。  
  
Eloquent の Model に所定の Interface を実装するだけで、  
そのモデルは自動的に管理画面から編集可能になります。  
追加の設定や定義は必要ありません。  
  
Laravel Vein は、コンテンツの登録・更新といった **管理機能に特化**しており、  
表示機能やフロントエンド向けの API はあえて提供していません。  
コンテンツの取得・公開・描画方法は、アプリケーション側に委ねられます。  
  
本パッケージは、既存の Laravel アプリケーションに自然に組み込める CMS コアとして設計されており、  
Blade、REST API、GraphQL、Inertia、Livewire など、どのような構成とも共存可能です。  
  
ドメインモデルを中心に据えた設計で、  
フロントエンドや API の構成を強制しない CMS を求める開発者のためのパッケージです。  

## What's Laravel Vein

Laravel Vein is a **developer-oriented Content Management System (CMS)** for Laravel applications.  
  
By simply implementing a predefined interface on your Eloquent models,  
they automatically become editable through an administrative UI—no additional configuration required.  
  
Laravel Vein focuses on **content creation and management**, while intentionally **excluding frontend rendering and content delivery APIs**.  
This design allows developers to fully control how content is queried, exposed, and displayed within their applications.  
  
Built to be **embedded into Laravel applications**, Laravel Vein adapts naturally to your existing architecture,  
whether you use Blade, REST APIs, GraphQL, Inertia, Livewire, or any custom approach.  
  
If you are looking for a lightweight CMS core that integrates seamlessly with your domain models and does not impose frontend opinions, Laravel Vein is built for you.  
  
  
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
Entry を分類するために存在する、カテゴリやタグなどの概念。  
Entry と N対1（単一選択）か、N対N（複数選択）かを問わない。  
通常、ID、名称、並び順のみを持つ。  
（説明文やサムネイル画像等、多くの情報をもつ場合は Entry として扱う）。
  
### Page  
サイト内で常に1つだけ存在するコンテンツ。  
会社案内やプライバシーポリシーなど。  
  
### Record  
Entry または Page と1対Nのサブデータとなるもの。  
独立したページにはならない。  
例えば、商品Entryにおけるカラーバリエーション（名称、品番、価格）や、  
会社案内Pageにおける沿革（年、月、概要、説明文）など。  
  
### File  
上記いずれかに紐づく、画像などの添付ファイル。  
Entry 等と1:1 か、1:N かを問わない。  
  
### Node  
上記全てを包括する概念。  
基本的に全てのNodeはModelで表現される。  
（関わる全てのModelがNodeなわけではない）  
  
## データベース設計のルール  
### ファイルアップロード (File)  
アップロードファイルは、主データ (Entry, Taxonomy, Record, Page) と 1:1 か、1:N かを問わず、  
ファイルの種類ごとに独立したモデルとして扱う必要がある。  
1:1 の場合は BelongsTo リレーション、1:N の場合は HasMany リレーションとして定義される必要がある。  
(HasOne リレーションには対応していない)  
  
### 独立ページ (Page)  
例えばトップページや会社概要のような、恒久的に1ページしか存在しないようなページを管理画面から更新可能にしたい場合、  
ページごとに専用のモデルを用意する必要がある。  
言い換えると、常に1レコードしか存在しないテープルを作る必要がある。  

### 複数選択項目
チェックボックス等で複数選択する性質の値は、選択する値が別テーブルの値であれ、Enumであれ、  
サブテーブルとして格納される必要がある。  
(カンマ区切り文字列や、配列型・JSON型での格納には対応していない)  
また、HasMany リレーションとして定義される必要がある。
(BelongsToMany リレーションには対応していない)  
    
# 初期設定  
## 管理画面ログイン用 Auth の設定  
コンテンツ管理画面へのログインには、  
デフォルトでは config/auth.php に設定されたデフォルトガードを使用する。  
それ以外のガードを使用する場合は、config/auth.php にガードを定義の上、  
config/vein.php の **admin_guard** に設定する。  
どちらの場合でも、SessionGuard を使用したガードである必要がある。  
  
## 管理画面URLの変更  
config/vein.php の **admin_uri** に設定する。  
  
  
# リファレンス（作成中）
- Entry の基本
- Entry の一覧画面
- [Entry の編集画面](doc/entry-edit.md)
- Taxonomy の基本
- Taxonomy の一覧・編集画面
- Page の基本
- Page の編集画面
- Record の基本
- Record の編集UI
- ListField の詳細
- [FormControl の種類](doc/input-variations.md)
- ファイルアップロードについて
- 独自 Input の作成

# 開発予定 (TODO)  
## ログイン関連  
- パスワードリマインダー
- ユーザ管理画面  
- パスワード変更画面
  
## Inputの追加  
- SelectEnum (済)  
- SelectModel (済)    
- RadioEnum  
- RadioModel  
- SelectMultipleEnum  
- SelectMultipleModel  
- CheckboxesEnum (済)    
- CheckboxesModel  
- Wysiwyg  
- FileUpload (済)  
- FileUploadMultiple  
- Records  
  
## Entry以外のNodeの対応  
- Taxonomy  
- Page

## その他
- メニューの階層化
- 各ファイルに記載された TODO
