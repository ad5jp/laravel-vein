@extends('vein::layout')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $model->menuName() }} 新規登録</h1>
        <a href="{{ route('vein.list', ['node' => $node]) }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
    <section class="section">
        {{-- TODO メッセージ --}}
        <form action="{{ route('vein.add', ['node' => $node]) }}" method="post">
            @csrf
            @foreach ($editFields as $editField)
            {!! $editField->render() !!}
            @endforeach
            <div class="text-end">
                <button type="submit" class="btn btn-primary">登録</button>
            </div>
        </form>
    </section>
</div>
@endsection
