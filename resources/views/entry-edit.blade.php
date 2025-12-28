@extends('vein::layout')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $model->menuName() }} 編集</h1>
        <a href="{{ route('vein.list', ['node' => $node]) }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
    <section class="section">
        {{-- TODO メッセージ --}}
        <form action="{{ route('vein.edit', ['node' => $node, 'id' => $entry->getKey()]) }}" method="post">
            @csrf
            @foreach ($editFields as $editField)
            <div class="row mb-3">
                <div class="{{ $editField->columnClass() }}">
                    {!! $editField->render($entry) !!}
                </div>
            </div>
            @endforeach
            <div class="text-end">
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
            <div class="text-start">
                <button type="submit" class="btn btn-sm btn-outline-danger" form="delete">削除</button>
            </div>
        </form>
    </section>

    {{-- TODO 確認ダイアログ --}}
    <form action="{{ route('vein.delete', ['node' => $node, 'id' => $entry->getKey()]) }}" method="post" id="delete">
        @csrf
    </form>
</div>
@endsection
