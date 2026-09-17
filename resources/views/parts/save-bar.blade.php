<?php
/**
 * フォームの保存ボタン。削除も同じ行に置く。
 *
 * 入力欄が増えると画面の外へ出てしまい、保存のたびに最下部まで運ぶことになる。
 * 画面の下に貼り付けて、どこを編集していても押せるようにする。
 *
 * 削除のフォームは別にある（フォームは入れ子にできない）。ボタンの form 属性で
 * そちらへ送り、見た目だけをこの行に持ってくる。$deleteLabel を渡した画面にだけ出る。
 *
 * 消せない行（自分自身・最後の 1 人）では $deleteLabel の代わりに $deleteNote が
 * 来る。ボタンを出さず、なぜ無いのかをその場に書く。
 *
 * @see resources/views/entry-edit.blade.php
 * @see resources/views/entry-add.blade.php
 */
?><style>
.__save_bar {
    position: sticky;
    bottom: 0;
    z-index: 10;
    display: flex;
    /* 削除のラベルは「この○○を削除」で、名前が長いと 1 行に収まらない。
       折り返さないと更新ボタンが縦に潰れる */
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
    gap: 0.75rem;
    /* .section の padding を打ち消し、カードの下端にぴったり合わせる */
    margin: 0 -1rem -1rem;
    padding: 0.75rem 1rem;
    background: #FFF;
    border-top: 1px solid #DEE2E6;
    border-radius: 0 0 0.5rem 0.5rem;
}
/* 更新との距離を、その画面で取れるだけ取る */
.__save_bar--delete { margin-right: auto; }
.__save_bar--note {
    margin: 0 auto 0 0;
    /* ボタンより小さく、灰色で。押せるものと見間違えさせない */
    font-size: 0.875rem;
    color: #6C757D;
}
</style>
<div class="__save_bar">
    @if (! empty($deleteLabel))
    <button type="submit" form="delete"
        class="btn btn-sm btn-outline-danger __confirm_delete __save_bar--delete">{{ $deleteLabel }}</button>
    @elseif (! empty($deleteNote))
    <p class="__save_bar--note"><i class="bi bi-lock" aria-hidden="true"></i> {{ $deleteNote }}</p>
    @endif
    <button type="submit" class="btn btn-primary">{{ $label }}</button>
</div>
