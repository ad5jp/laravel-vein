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
</style>
<script>
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
        start: function (event, ui) {
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

    $(this).closest('.__records_list_item').remove();
});
</script>