<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form;

class UploadService
{
    public function forPreview(string $file, string $mime_type, string $file_name): string
    {
        // 画像なら base 64
        if (str_starts_with($mime_type, 'image/')) {
            return sprintf(
                'data:%s;base64,%s',
                $mime_type,
                base64_encode($file),
            );
        }

        // そうでなければファイル名画像
        // TODO アイコンくらいつけたい
        $width = mb_strwidth($file_name) * 10 + 20;
        $text = htmlspecialchars($file_name, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="40">'
            . '<style>'
            . '    text {font-size: 20px; fill: #333; }'
            . '</style>'
            . '<text x="10" y="30">%s</text>'
            . '</svg>',
            $width,
            $text
        );

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
