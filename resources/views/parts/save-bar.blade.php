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
 * @see resources/views/entry-edit.blade.php
 * @see resources/views/entry-add.blade.php
 */
?><style>
.__save_bar {
    position: sticky;
    bottom: 0;
    z-index: 10;
    display: flex;
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
</style>
<div class="__save_bar">
    @isset($deleteLabel)
    <button type="submit" form="delete"
        class="btn btn-sm btn-outline-danger __confirm_delete __save_bar--delete">{{ $deleteLabel }}</button>
    @endisset
    <button type="submit" class="btn btn-primary">{{ $label }}</button>
</div>
