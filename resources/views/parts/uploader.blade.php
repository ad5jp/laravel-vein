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
</style>
<script>
$(document).on('click', '.__records_add', function () {
    const $records = $(this).closest('.__records');

    const index = $records.data('nextkey');
    $records.data('nextkey', index + 1)
    const template_html = $records.find('script').html().replaceAll('[0]', '[' + index + ']');
    $records.find('.__records_list').append($(template_html));
});
$(document).on('click', '.__records_remove', function () {
    if (!confirm('この行を削除しますか？')) {
        return;
    }

    $(this).closest('.__records_list_item').remove();
});
</script>