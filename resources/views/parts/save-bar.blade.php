<?php
/**
 * フォームの保存ボタン。
 *
 * 入力欄が増えると画面の外へ出てしまい、保存のたびに最下部まで運ぶことになる。
 * 画面の下に貼り付けて、どこを編集していても押せるようにする。
 *
 * @see resources/views/entry-edit.blade.php
 * @see resources/views/entry-add.blade.php
 */
?><style>
.__save_bar {
    position: sticky;
    bottom: 0;
    z-index: 10;
    /* .section の padding を打ち消し、カードの下端にぴったり合わせる */
    margin: 0 -1rem -1rem;
    padding: 0.75rem 1rem;
    background: #FFF;
    border-top: 1px solid #DEE2E6;
    border-radius: 0 0 0.5rem 0.5rem;
}
</style>
<div class="__save_bar text-end">
    <button type="submit" class="btn btn-primary">{{ $label }}</button>
</div>
