@extends('vein::layout')

@section('content')
<div class="container">
    <div class="__page_head">
        <h1 class="mb-0">{{ $model->menuName() }} 編集</h1>
        <div class="d-flex gap-2">
            {{-- 公開側の URL を持っているノードでは、そこへ出られるようにする。
                 下書きなど、まだ見られない状態のときは null を返してもらう --}}
            @if (method_exists($record, 'publicUrl') && $record->publicUrl())
            <a href="{{ $record->publicUrl() }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> 公開ページ
            </a>
            @endif
            <a href="{{ route('vein.list', ['node' => $node]) }}" class="btn btn-secondary">一覧に戻る</a>
        </div>
    </div>
    <div class="__edit_body">
    <section class="section">
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('vein.edit', ['node' => $node, 'id' => $record->getKey()]) }}" method="post">
            @csrf
            @foreach ($editFields as $editField)
            {!! $editField->render($record) !!}
            @endforeach
            @include('vein::parts.save-bar', ['label' => '更新'])
        </form>
    </section>
    @include('vein::parts.section-nav', ['editFields' => $editFields])
    </div>

    {{-- 更新から離して置く。並ぶと、更新のつもりで削除を押す距離になる --}}
    <section class="section mt-5 pt-4 border-top">
        <form action="{{ route('vein.delete', ['node' => $node, 'id' => $record->getKey()]) }}" method="post" id="delete">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger __confirm_delete">この{{ $model->menuName() }}を削除</button>
            <p class="form-text mb-0">削除すると元に戻せません。</p>
        </form>
    </section>
</div>

@include('vein::parts.uploader')
@endsection
