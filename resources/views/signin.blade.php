@extends('vein::layout')

@section('content')
<div class="container max-width-sm">
    <h1 class="page-title">ログイン</h1>
    <section class="section">
        {{-- TODO メッセージ --}}
        <form action="{{ route('vein.signin') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label">メールアドレス</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">パスワード</label>
                <input type="password" name="password" class="form-control" value="{{ old('password') }}">
            </div>
            <div class="text-end">
                <button class="btn btn-primary">ログイン</button>
            </div>
        </form>
    </section>
</div>
@endsection
