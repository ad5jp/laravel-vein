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

        const fd = new FormData();
        fd.append('upload', $(this).prop('files')[0]);
        fd.append('_token', $('meta[name="csrf"]').prop('content'));
        fetch(api, {
            method: 'POST',
            body: fd
        })
        .then(response => response.json())
        .then(data => {
            const key = $uploader.data('key');
            const $preview_item = $('<div class="__uploader_preview_item col-6 col-md-3"><img src=""><input type="hidden" name="" value=""><button class="__uploader_preview_remove" type="button"></button></div>');
            $preview_item.find('img').attr('src', data.preview);
            $preview_item.find('input').attr('name', key);
            $preview_item.find('input').attr('value', data.value);
            // $uploader.find('.__uploader_preview').prepend($preview_item);
            $uploader.find('.__uploader_preview').html($preview_item);
        })
        .catch((error) => {
            console.error(error);
        });

        $(this).val(null);
    });

    $(document).on('click', '.__uploader_preview_remove', function () {
        const $item = $(this).closest('.__uploader_preview_item');
        $item.remove();
    });
});
</script>
