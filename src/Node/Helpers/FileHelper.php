<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Helpers;

trait FileHelper
{
    public function getFileName(): string
    {
        return $this->file_name;
    }

    public function getFilePath(): string
    {
        return $this->file_path;
    }

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function getFileSize(): int
    {
        return $this->file_size;
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
