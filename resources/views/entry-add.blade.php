@extends('vein::layout')

@section('content')
<div class="container">
    <div class="__page_head">
        <h1 class="mb-0">{{ $model->menuName() }} 新規登録</h1>
        <a href="{{ route('vein.list', ['node' => $node]) }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
    <div class="__edit_body">
    {{-- 目次は「欄まで運ぶ」ためのもの。キーボードと読み上げでも先に届くよう
         本体より前に置き、見た目だけ右へ寄せる（CSS の order）。
         すべて空で一番長い画面なので、編集より要る --}}
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

        <form action="{{ route('vein.add', ['node' => $node]) }}" method="post">
            @csrf
            @foreach ($editFields as $editField)
            {!! $editField->render($record) !!}
            @endforeach
            @include('vein::parts.save-bar', ['label' => '登録'])
        </form>
    </section>
    </div>
</div>

@include('vein::parts.uploader')
@endsection
