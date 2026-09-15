<?php

declare(strict_types=1);

namespace AD5jp\Vein\Form\Input;

use AD5jp\Vein\Form\Contracts\Form;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

/**
 * 入力欄の並びを見出しで区切る。
 *
 * 項目が多いノードでは、フォームが縦に数千 px になる。まとまりの頭に見出しを置くと、
 * どこに何があるかが分かる。編集画面は見出しから目次も作る。
 *
 * 保存には関与しない。
 */
class Section implements Form
{
    public function __construct(
        public string $label,
        public ?string $note = null,
    ) {}

    public function render(Model $values): string
    {
        return sprintf(
            '<div class="__form_section" id="%s">'
            .'<h2 class="__form_section--title">%s</h2>'
            .'%s'
            .'</div>',
            e($this->anchor()),
            e($this->label),
            $this->note === null ? '' : sprintf('<p class="__form_section--note">%s</p>', e($this->note)),
        );
    }

    /**
     * 目次から飛ぶための id。
     *
     * 見出しは日本語のことが多く、そのままでは id に使えない。順番は保証できないので
     * 内容から作る。
     */
    public function anchor(): string
    {
        return '__section_'.substr(md5($this->label), 0, 8);
    }

    public function renderColumn(Model $values): string
    {
        return $this->render($values);
    }

    public function renderInline(Model $values): string
    {
        return $this->render($values);
    }

    public function beforeSave(Model $values, Arrayable|array $request): Model
    {
        return $values;
    }

    public function afterSave(Model $values, Arrayable|array $request): Model
    {
        return $values;
    }
}
