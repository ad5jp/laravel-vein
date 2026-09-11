<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form;

class UploadService
{
    /**
     * RFC 6838 の形。data URI に差し込むので、属性を閉じる文字が混じらないことを確かめる。
     */
    private const MIME_TYPE_PATTERN = '#^[a-z0-9][a-z0-9!\#$&^_.+-]{0,126}/[a-z0-9][a-z0-9!\#$&^_.+-]{0,126}$#i';

    public function forPreview(string $file, string $mime_type, string $file_name): string
    {
        // 画像なら base 64。mime_type は hidden input 由来のことがあるため形式を確かめる
        if (str_starts_with($mime_type, 'image/') && preg_match(self::MIME_TYPE_PATTERN, $mime_type) === 1) {
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
            .'<style>'
            .'    text {font-size: 20px; fill: #333; }'
            .'</style>'
            .'<text x="10" y="30">%s</text>'
            .'</svg>',
            $width,
            $text
        );

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
