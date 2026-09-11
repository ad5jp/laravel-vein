@extends('vein::layout')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $model->menuName() }} 編集</h1>
        <a href="{{ route('vein.list', ['node' => $node]) }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
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
            <div class="text-end">
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
            <div class="text-start">
                <button type="submit" class="btn btn-sm btn-outline-danger" form="delete">削除</button>
            </div>
        </form>
    </section>

    {{-- TODO 確認ダイアログ --}}
    <form action="{{ route('vein.delete', ['node' => $node, 'id' => $record->getKey()]) }}" method="post" id="delete">
        @csrf
    </form>
</div>

@include('vein::parts.uploader')
@endsection
