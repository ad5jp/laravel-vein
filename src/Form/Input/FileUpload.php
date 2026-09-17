<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Concerns\ResolvesRelations;
use AD5jp\Vein\Form\Contracts\DeletesRelated;
use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\PendingFileDeletion;
use AD5jp\Vein\Form\UploadService;
use AD5jp\Vein\Node\Contracts\File;
use Closure;
use Exception;
use finfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\MimeTypes;

class FileUpload extends FormControl implements DeletesRelated, Form
{
    use ResolvesRelations;

    private function relation(Model $model): BelongsTo
    {
        /** @var BelongsTo */
        return $this->resolveRelation($model, $this->key, BelongsTo::class, File::class);
    }

    // TODO MimeType の指定
    public function __construct(
        public string $key,
        public ?string $disk = null,
        public ?string $directory = null,
        /** @var string[]|null 許可する拡張子。null なら config/vein.php の既定 */
        public ?array $extensions = null,
        public ?string $label = null,
        public mixed $default = null, // ファイルアップロードにデフォルトは無効
        public int $colSize = 12,
        public bool $required = false,
        public ?Closure $beforeSaving = null,
        public ?Closure $afterSaving = null,
        public ?Closure $searching = null,
    ) {
        $this->disk = $this->disk ?: config('vein.upload_disk');
        $this->directory = $this->directory ?: config('vein.upload_path');
        $this->directory = trim($this->directory, '/');
        $this->extensions = $this->extensions ?: config('vein.upload_extensions');

        parent::__construct($key, $label, $default, $colSize, $required, $beforeSaving, $afterSaving, $searching);
    }

    /**
     * 選んだ画像 1 枚分。
     *
     * 代替テキストにはこの欄の名前を入れる。中身までは分からないが、
     * 「何の画像か」は伝わる。名前が無ければ飾り扱いで空にする。
     */
    private function previewHtml(string $preview, string $value): string
    {
        return sprintf(
            '<div class="__uploader_preview_item col-6 col-md-3">'
            .'<img src="%s" alt="%s">'
            .'<input type="hidden" name="%s" value="%s">'
            .'<button class="__uploader_preview_remove" type="button" aria-label="%s"></button>'
            .'</div>',
            e($preview),
            e($this->label ?? ''),
            e($this->key),
            e($value),
            e($this->label === null ? '画像を外す' : $this->label.'を外す'),
        );
    }

    protected bool $labelled_input = true;

    public function renderInline(Model $values): string
    {
        $this->relation($values);

        $preview_html = '';

        $file = $values->{$this->key};
        $old = old($this->key);

        if ($old) {
            if (str_starts_with($old, '{"')) {
                // 新規アップロードファイルの old
                $json = json_decode($old, true);
                $tmp_file = Storage::disk(config('vein.temporary_disk'))->get($json['tmp_path']);
                $service = new UploadService;
                $preview = $service->forPreview($tmp_file, $json['mime_type'], $json['file_name']);

                $preview_html = $this->previewHtml($preview, $old);
            } else {
                // アップロード済IDの old
                $belongsTo = $this->relation($values);
                $related_model = $belongsTo->getRelated();
                $file = $related_model->find($old);
                if ($file instanceof File) {
                    $stored_file = Storage::disk($this->disk)->get($file->getFilePath());
                    $service = new UploadService;
                    $preview = $service->forPreview($stored_file, $file->getMimeType(), $file->getFileName());

                    $preview_html = $this->previewHtml($preview, $old);
                }
            }
        } elseif ($file instanceof Model && $file instanceof File) {
            // 編集画面の初期表示時
            $stored_file = Storage::disk($this->disk)->get($file->getFilePath());
            $service = new UploadService;
            $preview = $service->forPreview($stored_file, $file->getMimeType(), $file->getFileName());

            $preview_html = $this->previewHtml($preview, (string) $file->getKey());
        }

        $html = sprintf(
            '<div class="__uploader" data-key="%s">'
            .'<div class="__uploader_preview row mb-2">%s</div>'
            .'<input type="file" class="__uploader_input"%s>'
            .'<p class="__uploader_status form-text mb-0"></p>'
            .'</div>',
            e($this->key),
            $preview_html,
            $this->inputAttributes($values),
        );

        return $html;
    }

    /**
     * 親が削除されるとき、紐づくファイルを「あとで消す対象」として返す。
     *
     * ここでは消さない。親がまだこのファイルを指しているため、先に消すと
     * 外部キーに弾かれる。実際に消すのは親が消えたあと（NodeDeleter）。
     */
    public function collectFiles(Model $model, bool $row_will_be_removed): array
    {
        // 行が残るなら、その行がまだ指しているのでファイルは消せない（外部キーに弾かれる）
        if (! $row_will_be_removed) {
            return [];
        }

        $this->relation($model);

        $file = $model->{$this->key};

        if (! $file instanceof Model || ! $file instanceof File) {
            return [];
        }

        return [new PendingFileDeletion($file, $this->disk)];
    }

    /**
     * ファイルのレコードは NodeDeleter が最後に消す。ここでは何もしない。
     */
    public function deleteRows(Model $model): void {}

    /**
     * 中身から形式を判定し、許可した拡張子に対応するものだけを通す。
     *
     * Storage::mimeType() は拡張子から推測するため、中身がテキストでも
     * .png という名前なら image/png を返す。ここでは使えない。
     */
    private function detectMimeType(string $contents): string
    {
        $mime_type = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if ($mime_type === false) {
            throw new Exception('ファイルの形式を判定できませんでした');
        }

        $extensions = MimeTypes::getDefault()->getExtensions($mime_type);

        if (array_intersect($extensions, $this->extensions) === []) {
            throw new Exception('許可されていない形式のファイルです: '.$mime_type);
        }

        return $mime_type;
    }

    /**
     * 実体の削除をコミット後に回す。
     *
     * Storage はトランザクションの対象外なので、その場で消すとロールバックしても戻らない。
     * トランザクションの外で呼ばれた場合は、その場で実行される。
     */
    private function deleteAfterCommit(string $path): void
    {
        $disk = $this->disk;

        DB::afterCommit(static fn () => Storage::disk($disk)->delete($path));
    }

    /**
     * 置いた実体を、ロールバックしたときに片付ける。
     */
    private function deleteAfterRollback(string $path): void
    {
        $disk = $this->disk;

        DB::afterRollBack(static fn () => Storage::disk($disk)->delete($path));
    }

    /**
     * 差し替えで不要になった旧ファイル。保存が済むまで消せないので控えておく。
     *
     * @var list<Model&File>
     */
    private array $pending_old_files = [];

    /**
     * 親の外部キーが新しいファイルに向いたあとで、旧ファイルのレコードを消す。
     *
     * 実体の削除は applyBeforeSave で afterCommit に予約済み。ここで消すのは
     * レコードだけ。
     */
    protected function applyAfterSave(Model $model, array $request): Model
    {
        foreach ($this->pending_old_files as $old_file) {
            $old_file->delete();
        }

        $this->pending_old_files = [];

        return $model;
    }

    protected function applyBeforeSave(Model $model, array $request): Model
    {
        $belongsTo = $this->relation($model);
        /** @var Model&File $file */
        $foreign_key = $belongsTo->getForeignKeyName();

        $value = $request[$this->key] ?? null;
        if ($model->getKeyType() !== 'string' && is_numeric($value)) {
            $value = (int) $value;
        }

        // 値に変化がなければ、何もしない
        if ($value === $model->$foreign_key) {
            return $model;
        }

        // 変化があり、かつ変更前の値があるなら、変更前のファイルを片付ける。
        // ただしレコードの削除は保存の後（applyAfterSave）。この時点では $model が
        // まだ旧ファイルを指しており、先に消すと外部キーに弾かれる
        if ($model->{$this->key}) {
            /** @var Model&File $old_file */
            $old_file = $model->{$this->key};
            // 実体の削除はコミット後。ここで消すと、後続が失敗したときに
            // DB にはレコードがあるのに実体だけ無い状態になる
            $this->deleteAfterCommit($old_file->getFilePath());
            $this->pending_old_files[] = $old_file;
        }

        // 変更後の値がなけれれば null にして終了
        if ($value === null) {
            $model->$foreign_key = null;

            return $model;
        }

        // 一時ファイル情報 (JSON文字列) が送信されてきた場合
        if (str_starts_with($value, '{"')) {
            $json = json_decode($value, true);

            // 正規ディレクトリに移動
            $tmp_file = Storage::disk(config('vein.temporary_disk'))->get($json['tmp_path']);

            // 送られてきた mime_type は hidden input 由来なので信用しない。
            // 名前ではなく中身から判定し直す（Storage::mimeType() は拡張子を見るだけ）
            $mime_type = $this->detectMimeType($tmp_file);

            $store_path = $this->directory.'/'.basename($json['tmp_path']);
            Storage::disk($this->disk)->put($store_path, $tmp_file);
            // 置いた実体はトランザクションで戻らないので、失敗したら自分で片付ける
            $this->deleteAfterRollback($store_path);

            // FILEモデルを保存
            /** @var Model&File $new_file */
            $new_file = $belongsTo->getRelated()->newInstance();
            $new_file->setFileName($json['file_name']);
            $new_file->setFilePath($store_path);
            $new_file->setMimeType($mime_type);
            // 送られてきた file_size も hidden input 由来なので信用しない。
            // 置いた実体から取る（mime_type と同じ扱い）
            $new_file->setFileSize(Storage::disk($this->disk)->size($store_path));
            $new_file->save();

            $model->$foreign_key = $new_file->getKey();

            return $model;
        }

        // 一時ファイルのパス以外 ＝ 保存済の ID が送信されてきた場合
        // （現状の実装では、IDが送られてくるのは値が変化していないときだけなので、ここに来ることはあり得ないが）
        $model->$foreign_key = $value;

        return $model;
    }
}
