@extends('vein::layout')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $model->menuName() }}</h1>
        <a href="{{ route('vein.add', ['node' => $node]) }}" class="btn btn-primary">新規</a>
    </div>
    <section class="section">
        {{-- TODO 検索フォーム --}}
        <table class="table">
            <thead>
                <tr>
                    @foreach ($listFields as $listField)
                    {{-- TODO ソート機能 --}}
                    <th>{{ $listField->label }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entries as $entry)
                <tr class="align-middle">
                    @foreach ($listFields as $listField)
                    <td>{{ $listField->getValue($entry) }}</td>
                    @endforeach
                    <td>
                        <a href="{{ route('vein.edit', ['node' => $node, 'id' => $entry->getKey()]) }}" class="btn btn-sm btn-primary">編集</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- TODO ページング --}}
    </section>
</div>
@endsection
