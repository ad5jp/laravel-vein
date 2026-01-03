<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Contracts;

interface File
{
    public function getFileName(): string;

    public function getFilePath(): string;

    public function getMimeType(): string;

    public function getFileSize(): int;

    public function setFileName(string $fileName): void;

    public function setFilePath(string $filePath): void;

    public function setMimeType(string $mimeType): void;

    public function setFileSize(int $fileSize): void;
}
