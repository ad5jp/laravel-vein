@extends('vein::layout')

@section('content')
<div class="container">
    <div class="__page_head">
        <h1 class="mb-0">{{ $model->menuName() }}の並べ替え</h1>
        <a href="{{ route('vein.list', ['node' => $node]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 一覧に戻る
        </a>
    </div>
    <p class="text-secondary">行をドラッグして並べ替えます。動かしたときにその場で保存され、この順で公開側にも出ます。</p>

    @foreach ($groups as $value => $group)
    <section class="section">
        @if ($group['label'] !== null)
        <h2 class="h5">{{ $group['label'] }}</h2>
        @endif

        @if ($group['entries']->isEmpty())
        <p class="text-secondary mb-0">登録がありません。</p>
        @else
        <ul class="list-group __order_list" data-group="{{ $value }}">
            @foreach ($group['entries'] as $entry)
            <li class="list-group-item d-flex align-items-center gap-2" data-id="{{ $entry->getKey() }}">
                <span class="__sort_handle text-secondary" aria-hidden="true"><i class="bi bi-list"></i></span>
                <span>{{ $entry->sortItemLabel() }}</span>
            </li>
            @endforeach
        </ul>
        <p class="small mt-2 mb-0 __order_status" role="status"></p>
        @endif
    </section>
    @endforeach
</div>

<script>
const sort_api = '{{ route("vein.sort", [$node]) }}';
const token = '{{ csrf_token() }}';

$(function () {
    $('.__order_list').sortable({
        handle: '.__sort_handle',
        update: function () {
            const $list = $(this);
            const $status = $list.nextAll('.__order_status').first();
            const ids = $list.children('[data-id]').map(function () { return $(this).data('id'); }).get();

            const payload = new FormData();
            payload.append('_token', token);
            payload.append('ids', ids.join(','));

            $status.text('保存しています…');

            fetch(sort_api, {
                method: 'POST',
                body: payload,
                headers: { 'Accept': 'application/json' },
            })
            .then(async (response) => {
                let json = null;
                try { json = await response.json(); } catch (e) { json = null; }

                // 転送された応答や JSON でない応答は失敗として扱う（転送先の HTML を成功と取り違えない）
                if (!response.ok || response.redirected || json === null) {
                    throw new Error((json && json.message) || '並び順を保存できませんでした。画面を読み込み直してください。');
                }

                $status.text('並び順を保存しました');
            })
            .catch((error) => {
                console.error(error);
                $status.text('');
                alert(error.message);
            });
        },
    });
});
</script>
@endsection
