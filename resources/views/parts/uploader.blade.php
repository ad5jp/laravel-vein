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
/* 掴んでいる行。中身は先頭の欄だけ見せる */
.__records_drag_bar {
    padding: 0.5rem 0.75rem 0.5rem 2.25rem; font-size: 0.9375rem; line-height: 1.75rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15); cursor: grabbing;
}
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
.__records_drag_bar::before {
    content: "\f4c2"; font-family: bootstrap-icons; position: absolute; left: 0.5rem;
    color: #6C757D; font-size: 1.25rem; line-height: 1.75rem;
}
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
            // 高い行をそのまま持ち上げると画面を覆う。掴んでいる間は 1 行分の帯にする
            // 高さも指定しておく。指定が無いと、掴んだ行の高さがそのまま写される
            return $('<div class="list-group-item __records_drag_bar"></div>')
                .css({ width: item.outerWidth(), height: 'auto' })
                .text(veinRowLabel(item));
        },
        start: function (event, ui) {
            // 持ち上げた行は 1 行分の帯になる。空いた場所も同じ高さにして、
            // 落ちる位置が指の近くに来るようにする
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