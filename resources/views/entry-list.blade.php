@extends('vein::layout')

@section('content')
<div class="container">
    <div class="__page_head">
        <h1 class="mb-0">{{ $model->menuName() }}</h1>
        {{-- 「新規」だけでは何が増えるのか読み取れないため、名前を入れる --}}
        <a href="{{ route('vein.add', ['node' => $node]) }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ $model->menuName() }}を追加
        </a>
    </div>
    <section class="section">
        @if (count($searchFields) > 0)
        <form action="{{ route('vein.list', ['node' => $node]) }}" class="row align-items-end mb-3">
            @foreach ($searchFields as $searchField)
            {!! $searchField->renderColumn($search) !!}
            @endforeach
            <div class="col">
                <button class="btn btn-primary">SEARCH</button>
            </div>
        </form>
        @endif

        {{-- 列が多いと狭い画面で画面ごと横に流れる。表だけを横に送る --}}
        <div class="table-responsive">
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
                @php($isMe = \AD5jp\Vein\Auth\LockoutGuard::isCurrentUser($entry))
                <tr class="align-middle">
                    @foreach ($listFields as $listField)
                    {{-- ログインできるノードでは、自分の行だけ印を付ける。
                         自分は消せないので、どれが自分か分からないと理由が通じない --}}
                    <td>{{ $listField->getValue($entry) }}@if ($isMe && $loop->first)
                        <span class="badge text-bg-secondary ms-2">自分</span>
                        @endif</td>
                    @endforeach
                    <td>
                        <a href="{{ route('vein.edit', ['node' => $node, 'id' => $entry->getKey()]) }}" class="btn btn-sm btn-primary">編集</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        {!! $entries->links() !!}
    </section>
</div>
@endsection
