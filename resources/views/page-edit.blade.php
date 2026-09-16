@extends('vein::layout')

@section('content')
<div class="container">
    <div class="__page_head">
        <h1 class="mb-0">{{ $model->menuName() }} 編集</h1>
        <div class="d-flex gap-2">
            {{-- 公開側の URL を持っているノードでは、そこへ出られるようにする。
                 下書きなど、まだ見られない状態のときは null を返してもらう。
                 一覧に戻るボタンは置かない。この型は一覧を持たない --}}
            @if (method_exists($record, 'publicUrl') && $record->publicUrl())
            <a href="{{ $record->publicUrl() }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> 公開ページ
            </a>
            @endif
        </div>
    </div>
    <div class="__edit_body">
    {{-- 目次は「欄まで運ぶ」ためのもの。キーボードと読み上げでも先に届くよう
         本体より前に置き、見た目だけ右へ寄せる（CSS の order） --}}
    @include('vein::parts.section-nav', ['editFields' => $editFields])
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

        <form action="{{ route('vein.page', ['node' => $node]) }}" method="post">
            @csrf
            @foreach ($editFields as $editField)
            {!! $editField->render($record) !!}
            @endforeach
            {{-- 削除は渡さない。この型は 1 行しか持たず、消す先が無い --}}
            @include('vein::parts.save-bar', ['label' => '更新'])
        </form>
    </section>
    </div>
</div>

@include('vein::parts.uploader')
@endsection
