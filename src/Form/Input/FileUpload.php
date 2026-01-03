<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use AD5jp\Vein\Form\UploadService;
use AD5jp\Vein\Node\Contracts\File;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUpload extends FormControl implements Form
{
    // TODO MimeType の指定
    public function __construct(
        public string $key,
        public ?string $disk = null,
        public ?string $directory = null,
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

        parent::__construct($key, $label, $default, $colSize, $required, $beforeSaving, $afterSaving, $searching);
    }

    public function render(?Model $values = null): string
    {
        $preview_html = '';

        $file = $values ? $values->{$this->key} : null;

        if ($file) {
            /** @var Model&File $file */
            $stored_file = Storage::disk($this->disk)->get($file->getFilePath());
            $service = new UploadService();
            $preview = $service->forPreview($stored_file, $file->getMimeType(), $file->getFileName());

            $preview_html = sprintf(
                '<div class="__uploader_preview_item col-6 col-md-3">'
                . '<img src="%s">'
                . '<input type="hidden" name="%s" value="%s">'
                . '<button class="__uploader_preview_remove" type="button"></button>'
                . '</div>',
                $preview,
                e($this->key),
                e($file->getKey()),
            );
        }


        $html = sprintf(
            '<div class="__uploader" data-key="%s">'
            . '<div class="__uploader_preview row mb-2">%s</div>'
            . '<input type="file" class="__uploader_input">'
            . '</div>',
            e($this->key),
            $preview_html,
        );

        return $this->wrap($html);
    }

    public function beforeSave(Model $model, Request $request): Model
    {
        $belongsTo = $this->verifyRelation($model, $this->key);
        /** @var Model&File $file */
        $foreign_key = $belongsTo->getForeignKeyName();

        $value = $request->input($this->key);

        // 値に変化がなければ、何もしない
        if ($model->$foreign_key === $value) {
            return $model;
        }

        // 変化があり、かつ変更前の値があるなら、変更前のファイルとモデルを削除する
        if ($model->{$this->key}) {
            /** @var Model&File $old_file */
            $old_file = $model->{$this->key};
            Storage::disk($this->disk)->delete($old_file->getFilePath());
            $old_file->delete();
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
            $store_path = $this->directory . '/' . basename($json['tmp_path']);
            Storage::disk($this->disk)->put($store_path, $tmp_file);

            // FILEモデルを保存
            /** @var Model&File $new_file */
            $new_file = $belongsTo->getRelated()->newInstance();
            $new_file->setFileName($json['file_name']);
            $new_file->setFilePath($store_path);
            $new_file->setMimeType($json['mime_type']);
            $new_file->setFileSize($json['file_size']);
            $new_file->save();

            $model->$foreign_key = $new_file->getKey();
            return $model;
        }

        // 一時ファイルのパス以外 ＝ 保存済の ID が送信されてきた場合
        // （現状の実装では、IDが送られてくるのは値が変化していないときだけなので、ここに来ることはあり得ないが）
        $model->$foreign_key = $value;
        return $model;
    }

    private function verifyRelation(Model $model, string $key): BelongsTo
    {
        foreach ([$key, Str::camel($key)] as $relation_method_name) {
            if (method_exists($model, $relation_method_name)) {
                $relation = $model->$relation_method_name();

                if (!$relation instanceof BelongsTo) {
                    throw new Exception('Model ' . get_class($model) . ' の ' . $relation_method_name . '() は BelongsTo リレーションではありません');
                }

                $file_model = $relation->getRelated();
                if (!$file_model instanceof File) {
                    throw new Exception('Model ' . get_class($file_model) . ' は File インターフェイスを実装していません');
                }

                return $relation;
            }
        }

        throw new Exception('Model ' . get_class($model) . ' にリレーション ' . $key . ' が定義されていません');
    }
}
