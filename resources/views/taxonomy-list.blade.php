@extends('vein::layout')

@php
// 行ごとに同じ欄を描くので、ラベルと欄を結ぶ id を行ごとに分ける。
// 分けないと、どの行のラベルを押しても 1 行目の欄に入る。
// 書き換えの規則は Records::wrapKeys() と同じ（__f_ / __l_ で始まる id だけを対象にする）
$scopeIds = static fn (string $html, string $suffix): string => preg_replace(
    '/\b(id|for|aria-labelledby)="__([fl])_(.*?)"/',
    '$1="__$2_$3__'.$suffix.'"',
    $html,
);
@endphp

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $model->menuName() }}</h1>
    </div>
    <section class="section">
        <div class="__list">
            @foreach ($taxonomies as $taxonomy)
            <form class="row align-items-end mb-3 __edit_form" data-id="{{ $taxonomy->getKey() }}">
                @csrf
                @if ($model->orderColumn())
                <div class="col __sort_handle bg-light py-2" style="flex-basis: 20px;"><i class="bi bi-list"></i></div>
                @endif
                <div class="col" style="flex-basis: calc(100% - 280px);">
                    <div class="row">
                        @foreach ($editFields as $editField)
                        {!! $scopeIds($editField->renderColumn($taxonomy), 'r'.$taxonomy->getKey()) !!}
                        @endforeach
                    </div>
                </div>
                <div class="col" style="flex-basis: 200px;">
                    <button class="btn btn-primary">保存</button>
                    <button class="btn btn-outline-danger __delete_button __confirm_delete" type="button">削除</button>
                    <span class="__row_status small ms-1" role="status"></span>
                </div>
            </form>
            @endforeach
        </div>
        <form class="row align-items-end mb-3 __add_form">
            @csrf
            @if ($model->orderColumn())
            <div class="col __sort_handle bg-light py-2" style="flex-basis: 20px; opacity: 0;"><i class="bi bi-list"></i></div>
            @endif
            <div class="col" style="flex-basis: calc(100% - 280px);">
                <div class="row">
                    @foreach ($editFields as $editField)
                        {!! $scopeIds($editField->renderColumn($model), 'new') !!}
                    @endforeach
                </div>
            </div>
            <div class="col __col_action" style="flex-basis: 200px;">
                <button class="btn btn-primary">追加</button>
                <span class="__row_status small ms-1" role="status"></span>
            </div>
        </form>
    </section>
</div>

<script>
const add_api = '{{ route("vein.add", [$node]) }}';
const edit_api = '{{ route("vein.edit", [$node, 9999]) }}';
const delete_api = '{{ route("vein.delete", [$node, 9999]) }}';
const sort_api = '{{ route("vein.sort", [$node]) }}';
const token = '{{ csrf_token() }}';
</script>
@if ($model->orderColumn())
<script>
$(function() {
    $(".__list").sortable({
        handle: ".__sort_handle",
        update: function () {
            try {
                const ids = [];
                $('.__edit_form').each(function () {
                    ids.push($(this).data('id'));
                });

                const payload = new FormData();
                payload.append('_token', token);
                payload.append('ids', ids);

                fetch(sort_api, {
                    method: 'POST',
                    body: payload,
                    headers: {
                        "Accept": "application/json"
                    },
                })
                .then(veinReadJson)
                .catch((error) => {
                    console.error(error);
                    alert('並べ替えを保存できませんでした。画面を読み込み直してください。');
                });
            } catch (e) {
                console.error(e);
            }
        }
    });
});
</script>
@endif
<script>
// 行ごとに送るので、画面を移動しない。保存バーを目印にする layout の離脱の確認は
// この画面では働かないため、打ちかけの行をここで数える
const dirtyForms = new Set();
let leaving = false;

$(document).on('input change', '.__edit_form, .__add_form', function () {
    dirtyForms.add(this);
    $(this).find('.__row_status').text('');
});

// 行の送信は画面を離れない。それ以外の送信（ログアウト等）は、layout と同じく黙って通す
document.addEventListener('submit', (event) => {
    if (!event.target.matches('.__edit_form, .__add_form')) {
        leaving = true;
    }
});

window.addEventListener('beforeunload', (event) => {
    if (dirtyForms.size === 0 || leaving) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
});

// 422 のときは errors を返す。veinReadJson は message しか残さないので、ここで読む。
// 転送された応答や JSON でない応答は失敗として扱う。fetch は転送先の HTML を 200 で
// 受け取るため、そのままだと「削除できなかった」が「削除できた」に見える
async function readResult(response) {
    let json = null;

    try {
        json = await response.json();
    } catch (e) {
        json = null;
    }

    return { ok: response.ok && !response.redirected && json !== null, status: response.status, json: json };
}

function clearErrors($form) {
    $form.find('.__has_error').removeClass('__has_error');
    $form.find('.__field_error').remove();
}

// 弾かれた欄を、ほかの編集画面と同じ見た目で示す。@see src/Form/Input/FormControl.php
function showErrors($form, errors) {
    Object.keys(errors || {}).forEach((key) => {
        const $input = $form.find('[name="' + key + '"], [name="' + key + '[]"]').first();
        const $column = $input.closest('[class*="col-"]');

        if (!$column.length) {
            return;
        }

        $column.addClass('__has_error');
        $column.append($('<p class="__field_error"></p>').text(errors[key][0]));
    });
}

function failed($form, result) {
    if (result.status === 422 && result.json && result.json.errors) {
        showErrors($form, result.json.errors);
        $form.find('.__row_status').text('保存できませんでした');
        return;
    }

    alert((result.json && result.json.message) || '処理できませんでした。時間をおいて試してください。');
}

$(document).on('submit', '.__edit_form', function () {
    const $form = $(this);
    const api = edit_api.replace('9999', $form.data('id'));

    clearErrors($form);
    $form.find('.__row_status').text('');

    fetch(api, {
        method: 'POST',
        body: new FormData(this),
        headers: {
            "Accept": "application/json"
        },
    })
    .then(readResult)
    .then((result) => {
        if (!result.ok) {
            failed($form, result);
            return;
        }

        dirtyForms.delete($form[0]);
        $form.find('.__row_status').text('保存しました');
    })
    .catch((error) => {
        console.error(error);
        alert('処理できませんでした。時間をおいて試してください。');
    });

    return false;
});

// 追加できたら一覧を読み直す。画面の上で行を複製すると、入力した値も id も写らない
$(document).on('submit', '.__add_form', function () {
    const $form = $(this);

    clearErrors($form);

    fetch(add_api, {
        method: 'POST',
        body: new FormData(this),
        headers: {
            "Accept": "application/json"
        },
    })
    .then(readResult)
    .then((result) => {
        if (!result.ok) {
            failed($form, result);
            return;
        }

        // 追加した行は保存済み。ほかの行に打ちかけがあれば、読み直す前に確認が出る
        dirtyForms.delete($form[0]);
        location.reload();
    })
    .catch((error) => {
        console.error(error);
        alert('処理できませんでした。時間をおいて試してください。');
    });

    return false;
});

$(document).on('click', '.__delete_button', function () {
    const $form = $(this).closest('form');
    const api = delete_api.replace('9999', $form.data('id'));

    fetch(api, {
        method: 'POST',
        body: new FormData($form[0]),
        headers: {
            "Accept": "application/json"
        },
    })
    .then(readResult)
    .then((result) => {
        // 使われている行は消せない。行は画面に残し、理由を出す
        if (!result.ok) {
            failed($form, result);
            return;
        }

        dirtyForms.delete($form[0]);
        $form.remove();
    })
    .catch((error) => {
        console.error(error);
        alert('処理できませんでした。時間をおいて試してください。');
    });

    return false;
});
</script>
@endsection
