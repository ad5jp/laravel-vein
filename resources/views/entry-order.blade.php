@extends('vein::layout')

@section('content')
<style>
/* 行は枠で囲まない。掴めることは取っ手・カーソル・乗せたときの背景（layout の __drag_item）で伝える */
.__order_list { margin: 0; padding: 0; list-style: none; }
.__order_item {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.5rem 0.75rem 0.5rem 0.25rem;
    border-radius: 0.375rem;
}
</style>
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
        <ul class="__order_list" data-group="{{ $value }}">
            @foreach ($group['entries'] as $entry)
            <li class="__order_item __drag_item" data-id="{{ $entry->getKey() }}">
                <span class="__drag_grip" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
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
    // 行は入力欄を持たないので、行のどこを掴んでも動かせるようにする
    $('.__order_list').sortable({
        axis: 'y',
        tolerance: 'pointer',
        placeholder: '__order_item __drag_placeholder',
        forcePlaceholderSize: true,
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
