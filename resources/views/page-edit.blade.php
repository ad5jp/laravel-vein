@extends('vein::layout')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $model->menuName() }} 編集</h1>
    </div>
    <section class="section">
        {{-- TODO メッセージ --}}
        <form action="{{ route('vein.page', ['node' => $node]) }}" method="post">
            @csrf
            @foreach ($editFields as $editField)
            {!! $editField->render($record) !!}
            @endforeach
            <div class="text-end">
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
        </form>
    </section>
</div>

@include('vein::parts.uploader')
@endsection
