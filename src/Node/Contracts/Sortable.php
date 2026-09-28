<?php

declare(strict_types=1);

namespace AD5jp\Vein\Node\Contracts;

/**
 * 並べ替えできる Entry。
 *
 * 一覧はページ送りと絞り込みがあってページをまたいで動かせないので、全件を並べた専用の画面
 * （/{node}/order）で並べ替える。一覧もこの並び順で並ぶ。
 *
 * 分類（Taxonomy）の orderColumn() とは別物。分類は一覧の画面そのものを並べ替える。
 */
interface Sortable
{
    /**
     * 並び順を持つ列。並べ替えると、ここに 0 から順に入る。
     */
    public function sortColumn(): string;

    /**
     * 並びを分ける列（例: 表示するページ）。並べ替えはこの列の値が同じ行の中だけで行う。
     * 分けないなら null。
     */
    public function sortGroupColumn(): ?string;

    /**
     * グループの値と見出し。並べ替えの画面はこの順にグループを出す。
     * ここに無い値の行も、値そのものを見出しにして後ろに出す。
     *
     * @return array<string, string>
     */
    public function sortGroups(): array;

    /**
     * 並べ替えの画面で、行を見分けるための 1 行の見出し。
     */
    public function sortItemLabel(): string;
}
