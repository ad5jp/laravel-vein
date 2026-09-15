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
.__records_remove {position: absolute; right: 0.5rem; top: 0; bottom: 0; width: 2rem; height: 2rem; margin: auto;}
/* 並べ替えるもの。つまむところを左に置き、その分だけ中身を右へ寄せる */
.__records_list_item.is-sortable {padding-left: 2.25rem;}
.__records_handle {position: absolute; left: 0.5rem; top: 0; bottom: 0; height: 2rem; margin: auto;
    display: flex; align-items: center; color: #ADB5BD; cursor: grab; font-size: 1.25rem;}
.__records_handle:active {cursor: grabbing;}
.__records_list_item.is-sortable:hover .__records_handle {color: #6C757D;}
/* ドラッグ中に空く場所。どこへ入るかが分かるようにする */
.__records_placeholder {border: 2px dashed #ADB5BD; border-radius: 0.375rem; background: #F8F9FA;}
/* 掴んでいる間は一覧を畳む。画像を持つ行は 480px ほどあり、そのままでは
   1 つ入れ替えるのに画面 1 枚分を運ぶことになる */
.__records_list.is-dragging .__records_list_item {
    max-height: 2.75rem; overflow: hidden; opacity: 0.7;
    padding-top: 0.5rem; padding-bottom: 0.5rem;
}
.__records_list.is-dragging .__records_remove {display: none;}
/* 掴んでいる行。中身は先頭の欄だけ見せる */
.__records_drag_bar {
    padding: 0.5rem 0.75rem 0.5rem 2.25rem; font-size: 0.9375rem; line-height: 1.75rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15); cursor: grabbing;
}
.__records_drag_bar::before {
    content: "\f4c2"; font-family: bootstrap-icons; position: absolute; left: 0.5rem;
    color: #6C757D; font-size: 1.25rem; line-height: 1.75rem;
}
</style>
<script>
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
    $records.find('.__records_list').append($(template_html));
});
// 行の並びをそのまま並び順として保存するため、画面で入れ替えられるようにする。
// つまむところを限ると、入力欄をなぞったときに行が動かない
$(function () {
    $('.__records_sortable').sortable({
        handle: '.__records_handle',
        axis: 'y',
        tolerance: 'pointer',
        placeholder: '__records_placeholder',
        forcePlaceholderSize: true,
        helper: function (event, item) {
            // 高い行をそのまま持ち上げると画面を覆う。掴んでいる間は 1 行分の帯にする
            return $('<div class="list-group-item __records_drag_bar"></div>')
                .css('width', item.outerWidth())
                .text(veinRowLabel(item));
        },
        start: function (event, ui) {
            var $list = $(this);

            // 一覧を畳んでから、どこへ落ちるかを測り直す
            $list.addClass('is-dragging');
            ui.placeholder.height(ui.helper.outerHeight());
            $list.sortable('refreshPositions');

            // 空いた場所の左端を控える。持ち上げた行は位置が絶対値に変わっているため、
            // 行そのものからは正しい値が取れない
            ui.item.data('veinLeft', ui.placeholder.offset().left);
        },
        stop: function (event, ui) {
            $(this).removeClass('is-dragging');
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

    $(this).closest('.__records_list_item').remove();
});
</script>