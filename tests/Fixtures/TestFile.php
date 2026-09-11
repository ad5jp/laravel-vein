<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use AD5jp\Vein\Node\Contracts\File;
use Illuminate\Database\Eloquent\Model;

/**
 * アップロードされたファイルのレコード。
 */
class TestFile extends Model implements File
{
    protected $table = 'test_files';

    protected $fillable = ['file_name', 'file_path', 'mime_type', 'file_size'];

    public function getFileName(): string
    {
        return (string) $this->file_name;
    }

    public function getFilePath(): string
    {
        return (string) $this->file_path;
    }

    public function getMimeType(): string
    {
        return (string) $this->mime_type;
    }

    public function getFileSize(): int
    {
        return (int) $this->file_size;
    }

    public function setFileName(string $fileName): void
    {
        $this->file_name = $fileName;
    }

    public function setFilePath(string $filePath): void
    {
        $this->file_path = $filePath;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mime_type = $mimeType;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->file_size = $fileSize;
    }
}
