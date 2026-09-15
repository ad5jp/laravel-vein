<?php
/**
 * @see src/Form/Input/FileUpload.php
 * @see src/Form/Input/FileUploadMultiple.php
 */
?><style>
.__uploader_preview_item {position: relative;}
.__uploader_preview_item img {width: 100%; aspect-ratio: 1; object-fit: contain; background: #EEE; border: 1px solid #EEE;}
.__uploader_preview_remove {position: absolute; right: -0; top: -10px; width: 30px; height: 30px; border: none; background: #000; opacity: 0.7;}
.__uploader_preview_remove::before {content: ""; position: absolute; left: 49%; top: 0; display: block; width: 2px; height: 30px; background: #FFF; transform: rotate(45deg);}
.__uploader_preview_remove::after {content: ""; position: absolute; left: 49%; top: 0; display: block; width: 2px; height: 30px; background: #FFF; transform: rotate(-45deg);}
</style>
<script>
const api = '{{ route('vein.upload') }}';
</script>
<script>
$(function () {
    $(document).on('change', '.__uploader_input', function () {
        const $uploader = $(this).closest('.__uploader');
        const $input = $(this);
        const file = $input.prop('files')[0];

        if (!file) {
            return;
        }

        const fd = new FormData();
        fd.append('upload', file);
        fd.append('_token', $('meta[name="csrf"]').prop('content'));

        // 送っている間は触れないようにする。何も出ないと「選んだのに何も起きなかった」に見える
        $input.prop('disabled', true);
        $uploader.find('.__uploader_status').text('アップロードしています…').removeClass('text-danger');

        fetch(api, {
            method: 'POST',
            body: fd,
            headers: {
                "Accept": "application/json"
            },
        })
        .then(veinReadJson)
        .then(data => {
            const key = $uploader.data('key');
            const $preview_item = $('<div class="__uploader_preview_item col-6 col-md-3"><img src=""><input type="hidden" name="" value=""><button class="__uploader_preview_remove" type="button"></button></div>');
            $preview_item.find('img').attr('src', data.preview);
            $preview_item.find('input').attr('name', key);
            $preview_item.find('input').attr('value', data.value);
            $uploader.find('.__uploader_preview').html($preview_item);
            $uploader.find('.__uploader_status').text('');
        })
        .catch((error) => {
            console.error(error);
            $uploader.find('.__uploader_status').text(error.message).addClass('text-danger');
        })
        .finally(() => {
            $input.prop('disabled', false);
            $input.val(null);
        });
    });

    $(document).on('click', '.__uploader_preview_remove', function () {
        const $item = $(this).closest('.__uploader_preview_item');
        $item.remove();
    });
});
</script>

<!-- TODO 別ファイルに切り出し -->
<style>
.__records_list_item {position: relative; padding-right: 3rem;}
.__records_list_item > .__records_remove {position: absolute; right: 0.5rem; top: 0; bottom: 0; width: 2rem; height: 2rem; margin: auto;}
/* 並べ替えるもの。つまむところを左に置き、その分だけ中身を右へ寄せる */
.__records_list_item.is-sortable {padding-left: 2.25rem;}
.__records_handle {position: absolute; left: 0.5rem; top: 0; bottom: 0; height: 2rem; margin: auto;
    display: flex; align-items: center; color: #ADB5BD; cursor: grab; font-size: 1.25rem;}
.__records_handle:active {cursor: grabbing;}
.__records_list_item.is-sortable:hover .__records_handle {color: #6C757D;}
/* ドラッグ中に空く場所。どこへ入るかが分かるようにする */
.__records_placeholder {border: 2px dashed #ADB5BD; border-radius: 0.375rem; background: #F8F9FA;}
/* 見出しの行と、畳むボタン */
.__records_head {display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem;}
.__records_head h5 {margin: 0;}
.__records_view {flex: none;}
/* 欄が 1 つしか無い子レコード。ラベルと段組みを外して 1 行に収める。
   何の欄かは見出しで分かるので、行ごとのラベルは重複になる */
.__records_list.is-single {border: 0;}
.__records_list.is-single .__records_list_item {
    display: flex; align-items: stretch;
    /* カードの枠と入力欄の枠で二重になるため、行側の枠と背景は外す。
       入力欄そのものを行として見せる */
    border: 0; background: transparent;
    padding: 0.25rem 0 0.25rem 1.75rem;
}
.__records_list.is-single .__records_handle {left: 0;}
.__records_list.is-single .__records_list_item > .row {
    flex: 1 1 auto; min-width: 0;
    /* .row が持つ mb-3 は Bootstrap のユーティリティで !important。
       残ると行の高さに 1rem 乗り、ごみ箱だけ背が高くなる */
    margin: 0 !important;
}
.__records_list.is-single .__records_list_item > .row > [class*="col-"] {
    flex: 1 1 auto; max-width: none; padding: 0;
}
.__records_list.is-single .__records_list_item .form-label {display: none;}
/* 入力とごみ箱をくっつける。枠線を 1px 重ねて 1 つの部品に見せる */
.__records_list.is-single .__records_list_item .form-control,
.__records_list.is-single .__records_list_item .form-select {
    border-top-right-radius: 0; border-bottom-right-radius: 0;
}
.__records_list.is-single .__records_list_item > .__records_remove {
    position: static; width: auto; height: auto; margin: 0 0 0 -1px;
    display: flex; align-items: center;
    border-top-left-radius: 0; border-bottom-left-radius: 0;
}

/* 一覧で表示したとき。並べ替えるときや、全体を見渡したいときに切り替える。
   入力欄は隠し、先頭の欄の中身だけを 1 行で出す（中途半端に見えていると読みづらい）。
   display: none でも値は送信されるので、この状態で保存しても中身は失われない */
.__records_list.is-compact {border: 0;}
.__records_list.is-compact .__records_list_item {
    /* 1 行の見出しをカードの枠で囲むと厚ぼったい。欄が 1 つの行と同じく、枠と背景を外す */
    border: 0; border-radius: 0; background: transparent;
    padding: 0.25rem 2.25rem 0.25rem 1.75rem; min-height: 2.25rem;
}
.__records_list.is-compact .__records_handle {left: 0;}
.__records_list.is-compact .__records_list_item > .__records_remove {right: 0;}
/* 枠が無くなると行の切れ目が分からない。細い線だけ残す */
.__records_list.is-compact .__records_list_item + .__records_list_item {border-top: 1px solid #F1F3F5;}
.__records_list.is-compact .__records_list_item > *:not(.__records_handle):not(.__records_remove):not(.__records_row_label) {
    display: none;
}
.__records_row_label {display: none;}
.__records_list.is-compact .__records_row_label {
    display: block; line-height: 1.75rem; color: #212529;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.__records_list.is-compact .__records_row_label:empty::before {content: '（空の行）'; color: #ADB5BD;}

/* 掴んでいる行。姿は変えず、浮いていることだけ見せる。
   枠を外した行は背景も透けるため、掴んでいる間だけ白で塗る */
.__records_list .__records_list_item.__records_dragging {
    background: #FFF; box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.18);
    cursor: grabbing; pointer-events: none;
}
/* 複製には指が乗らないので、hover の色が付かない。掴んでいる間は濃いままにする */
.__records_dragging .__records_handle {color: #6C757D;}
/* タイル。画像を持つ行はこちらで並べる */
/* grid や flex で並べると、jQuery UI が「縦一列」と見なして横の入れ替えができない。
   inline-block なら横並びと判定される */
.__records_tiles {font-size: 0;}
.__records_tile {
    display: inline-block; vertical-align: top; width: 11.5rem;
    margin: 0 0.75rem 0.75rem 0; font-size: 1rem;
}
.__records_tile_face {
    display: block; width: 100%; text-align: left; cursor: pointer;
    background: #FFF; border: 1px solid #DEE2E6; border-radius: 0.5rem; padding: 0.5rem;
}
.__records_tile_face:hover {border-color: #0D6EFD;}
.__records_tile_image {
    display: flex; align-items: center; justify-content: center;
    height: 7rem; margin-bottom: 0.5rem; overflow: hidden;
    background: #F8F9FA; border-radius: 0.375rem;
}
.__records_tile_image img {max-width: 100%; max-height: 100%; object-fit: contain;}
.__records_tile_blank {color: #ADB5BD; font-size: 0.8125rem;}
.__records_tile_label {
    display: block; font-size: 0.8125rem; line-height: 1.4; min-height: 2.8em;
    max-height: 2.8em; overflow: hidden; color: #212529;
}
.__records_tile_badge:not(:empty) {
    display: inline-block; margin-top: 0.25rem; padding: 0 0.375rem;
    font-size: 0.6875rem; color: #495057; background: #E9ECEF; border-radius: 0.25rem;
}
/* 弾かれた行 */
.__records_tile.is-error .__records_tile_face {
    border-color: #DC3545; box-shadow: 0 0 0 0.125rem rgba(220, 53, 69, 0.25);
}
/* 落とす先の枠。タイルと同じ形にしないと、全幅の帯になる */
.__records_tile_placeholder {
    display: inline-block; vertical-align: top; width: 11.5rem;
    margin: 0 0.75rem 0.75rem 0;
    border: 2px dashed #ADB5BD; border-radius: 0.5rem; background: #F8F9FA;
}
/* モーダルは 1 行分の幅しかないので、欄を横に並べず縦に積む */
.__records_tile_modal .modal-body > .row > [class*="col-"] {flex: 0 0 100%; max-width: 100%;}
</style>
<script>
// タイルの表側を、モーダルの中身から組み立てる。
// 画像を 2 回埋め込まずに済み、アップロードし直したときもついてくる
function veinSyncTile($tile) {
    var $modal = $tile.find('.__records_tile_modal');
    var $img = $modal.find('.__uploader_preview_item img').first();
    var $slot = $tile.find('.__records_tile_image').empty();

    if ($img.length) {
        $slot.append($('<img>').attr('src', $img.attr('src')));
    } else {
        $slot.append($('<span class="__records_tile_blank">画像なし</span>'));
    }

    // 見出しは文字の欄だけから取る。選択肢は下の印に出るので、二重に出さない
    var label = '';

    $modal.find('input[type="text"], textarea').each(function () {
        if (!label && $(this).val()) {
            label = $(this).val();
        }
    });

    $tile.find('.__records_tile_label').text(label || '（未入力）');
    $tile.find('.__records_tile_badge').text($modal.find('select').first().find('option:selected').text() || '');
    $tile.toggleClass('is-error', $modal.find('.__has_error, .__field_error').length > 0);
}

$(function () {
    $('.__records_tile').each(function () {
        veinSyncTile($(this));
    });

    // 弾かれた行は開いて見せる。閉じたままだと、どこが悪いのか分からない
    var $first = $('.__records_tile.is-error').first();

    if ($first.length && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance($first.find('.__records_tile_modal')[0]).show();
    }

    // タイルは 1 枚が小さいので、掴むところを分けず、少し動かしたら並べ替えにする。
    // そのままだと押して開けなくなる
    $('.__records_tiles.__records_sortable').sortable({
        items: '> .__records_tile',
        // タイルの表側はボタン。既定のままだと jQuery UI が掴むのを打ち消す
        cancel: 'input, textarea, select, option',
        distance: 6,
        tolerance: 'pointer',
        placeholder: '__records_tile_placeholder',
        forcePlaceholderSize: true,
    });
});

$(document).on('hidden.bs.modal input change', '.__records_tile', function () {
    veinSyncTile($(this));
});

// 掴んでいる行に出す見出し。先頭の埋まっている欄を使う
function veinRowLabel($item) {
    var text = '';

    $item.find('input[type="text"], textarea').each(function () {
        if (!text && $(this).val()) {
            text = $(this).val();
        }
    });

    if (!text) {
        $item.find('select').each(function () {
            var selected = $(this).find('option:selected').text();

            if (!text && selected) {
                text = selected;
            }
        });
    }

    return text || '（空の行）';
}

// カード表示 / 一覧表示の切り替え。行が高いと並べ替えづらく、全体も見渡せない。
// 掴んだ瞬間に自動で畳むと画面が飛ぶため、切り替えは明示的に行う
$(document).on('click', '.__records_view button', function () {
    const $btn = $(this);
    const $list = $btn.closest('.__records').find('.__records_list');

    const compact = $btn.data('view') === 'compact';

    // 一覧で表示するときは、先頭の欄の中身を見出しとして出す
    if (compact) {
        $list.find('.__records_list_item').each(function () {
            const $row = $(this);
            let $label = $row.children('.__records_row_label');

            if (!$label.length) {
                $label = $('<span class="__records_row_label"></span>').appendTo($row);
            }

            $label.text(veinRowLabel($row) === '（空の行）' ? '' : veinRowLabel($row));
        });
    }

    $list.toggleClass('is-compact', compact);

    $btn.addClass('active').attr('aria-pressed', 'true')
        .siblings().removeClass('active').attr('aria-pressed', 'false');

    // 高さが変わるので、落とす先を測り直す
    if ($list.hasClass('ui-sortable')) {
        $list.sortable('refreshPositions');
    }
});

$(document).on('click', '.__records_add', function () {
    const $records = $(this).closest('.__records');

    const index = $records.data('nextkey');
    $records.data('nextkey', index + 1)
    const template_html = $records.find('script').html().replaceAll('[0]', '[' + index + ']');
    const $added = $(template_html);

    // タイルはモーダルを持つ。id が重なると、いつも先頭の行が開いてしまう
    const $modal = $added.find('.__records_tile_modal');

    if ($modal.length) {
        const id = $modal.attr('id').replace(/_0$/, '_' + index);
        $modal.attr('id', id);
        $added.find('.__records_tile_face').attr('data-bs-target', '#' + id);
    }

    $records.find('.__records_list').append($added);

    if ($added.hasClass('__records_tile')) {
        veinSyncTile($added);
        window.bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }
});
// 行の並びをそのまま並び順として保存するため、画面で入れ替えられるようにする。
// つまむところを限ると、入力欄をなぞったときに行が動かない
$(function () {
    // タイルは別に組み立てる（つまむところを持たない）
    $('.__records_sortable').not('.__records_tiles').sortable({
        handle: '.__records_handle',
        axis: 'y',
        tolerance: 'pointer',
        placeholder: '__records_placeholder',
        forcePlaceholderSize: true,
        helper: function (event, item) {
            // 掴んだ行の姿のまま持ち上げる。帯に潰すと、何を動かしているのか分からない。
            // 行が高くて扱いづらいときは、一覧表示に切り替えてから並べ替える
            const $helper = item.clone();
            const $from = item.find('input, textarea, select');

            // 複製には打ち込み中の値が乗らない。元の欄から写す
            $helper.find('input, textarea, select').each(function (i) {
                const from = $from.get(i);

                if (!from) {
                    return;
                }

                if (this.type === 'checkbox' || this.type === 'radio') {
                    this.checked = from.checked;
                } else {
                    $(this).val($(from).val());
                }
            });

            // id が二重になると、見出しを押したときに複製側が反応することがある
            $helper.find('[id]').removeAttr('id');

            return $helper.addClass('__records_dragging')
                .css({ width: item.outerWidth(), height: item.outerHeight() });
        },
        start: function (event, ui) {
            // 空いた場所を掴んだ行と同じ高さにして、落ちる位置を指の近くに保つ
            ui.placeholder.height(ui.helper.outerHeight());
            $(this).sortable('refreshPositions');

            // 空いた場所の左端を控える。持ち上げた行は位置が絶対値に変わっているため、
            // 行そのものからは正しい値が取れない
            ui.item.data('veinLeft', ui.placeholder.offset().left);
        },
        sort: function (event, ui) {
            // 縦にしか動かさない作りだが、横に揺らすと左端が画面の端まで飛ぶことがある。
            // 掴んだときの左端に貼り付けて、横へは動かないようにする
            var left = ui.item.data('veinLeft');

            if (left === undefined) {
                return;
            }

            var current = ui.helper.offset();

            if (Math.round(current.left) !== Math.round(left)) {
                ui.helper.offset({ top: current.top, left: left });
            }
        },
    });
});
$(document).on('click', '.__records_remove', function () {
    if (!confirm('この行を削除しますか？')) {
        return;
    }

    const $tile = $(this).closest('.__records_tile');

    if ($tile.length) {
        // モーダルを閉じてから消す。開いたまま消すと、背景の覆いが残る
        window.bootstrap.Modal.getOrCreateInstance($tile.find('.__records_tile_modal')[0]).hide();
        $tile.remove();

        return;
    }

    $(this).closest('.__records_list_item').remove();
});
</script>