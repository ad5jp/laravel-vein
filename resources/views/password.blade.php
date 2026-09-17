@extends('vein::layout')

@section('content')
<div class="container max-width-sm">
  <h1 class="page-title">パスワードの変更</h1>
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

    <form action="{{ route('vein.password') }}" method="post">
      @csrf
      <div class="mb-3">
        <label class="form-label" for="__f_current_password">いまのパスワード</label>
        <input type="password" name="current_password" id="__f_current_password" class="form-control" autocomplete="current-password">
      </div>
      <div class="mb-3">
        <label class="form-label" for="__f_password">新しいパスワード</label>
        <input type="password" name="password" id="__f_password" class="form-control" autocomplete="new-password">
        <input type="password" name="password_confirmation" class="form-control mt-2" autocomplete="new-password" placeholder="確認のためもう一度" aria-label="新しいパスワード（確認のためもう一度）">
      </div>
      {{-- 変えたあともログインは保たれる。ほかの端末で開いている分だけ切れる --}}
      <p class="__field_hint">変えたあともこの画面の操作は続けられます。ほかの端末で開いている分はログアウトされます。</p>
      <div class="text-end">
        <button class="btn btn-primary">変更する</button>
      </div>
    </form>
  </section>
</div>
@endsection
