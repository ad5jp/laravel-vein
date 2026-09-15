<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vein</title>
    <link rel="stylesheet" href="{{ asset('vein-assets/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('vein-assets/bootstrap-icons.css') }}">
    <script src="{{ asset('vein-assets/bootstrap.js') }}"></script>
    <script src="{{ asset('vein-assets/jquery.js') }}"></script>
    <script src="{{ asset('vein-assets/jquery-ui.js') }}"></script>
    <meta name="csrf" content="{{ csrf_token() }}">
    <script>
    // 削除は 1 クリックで効いてしまうので、必ず一度止める。
    // 各画面のハンドラより先に束縛する必要があるため head に置いている
    $(document).on('click', '.__confirm_delete', function (event) {
      if (!confirm('削除すると元に戻せません。削除しますか？')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
      }
    });
    </script>
    <style>
    /* 弾かれた欄を目で追えるようにする。@see src/Form/Input/FormControl.php */
    .__has_error .form-control,
    .__has_error .form-select,
    .__has_error textarea,
    .__has_error input[type="file"] { border-color: #DC3545; }
    .__has_error .form-label { color: #DC3545; }
    .__field_error { color: #DC3545; font-size: 0.875em; margin: 0.25rem 0 0; }
    </style>
</head>
<body class="@yield('body_class')">

<nav class="navbar bg-dark border-bottom border-bottom-dark navbar-expand-md fixed-top" data-bs-theme="dark">
  <div class="container-fluid">
    <button class="navbar-brand btn" onclick="toggleSidebar()"><i class="bi bi-list"></i> Vein</button>
    @auth
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item ms-auto">
          <button class="nav-link" form="signout">ログアウト</button>
        </li>
      </ul>
    </div>
    @endauth
  </div>
</nav>

<form action="{{ route('vein.signout') }}" method="post" id="signout">@csrf</form>

<div class="wrap">
  @auth
  <div class="sidebar">
    <nav class="sidenav">
      <ul class="sidenav-list">
        <li class="sidenav-item">
          <a class="sidenav-link" aria-current="page" href="{{ route('vein.home') }}"><i class="bi bi-speedometer2"></i> ダッシュボード</a>
        </li>
        @foreach ($navs as $nav)
        <li class="sidenav-item">
          <a class="sidenav-link" href="{{ $nav->link }}"><i class="bi bi-{{ $nav->icon }}"></i> {{ $nav->label }}</a>
        </li>
        @endforeach
      </ul>
    </nav>
  </div>
  @endauth
  <main class="main">
    @foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $type => $style)
      @if (session()->has("message.{$type}"))
      <div class="container">
        <div class="alert alert-{{ $style }} alert-dismissible fade show" role="alert">
          {{ session("message.{$type}") }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
        </div>
      </div>
      @endif
    @endforeach
    @yield('content')
  </main>
</div>

<script>
// 弾かれたときは、最初に直すべき欄まで運ぶ。
// 入力欄が多いと、画面の上の一覧を見ても、その欄がどこにあるか分からない
$(function () {
  const first = document.querySelector('.__has_error');

  if (first) {
    first.scrollIntoView({ block: 'center' });
  }
});

function toggleSidebar() {
  $('body').toggleClass('md-sidebar-hide').toggleClass('sm-sidebar-show')
}

// 通信が失敗しているのに成功したように見えるのを防ぐ。
// 403 でも 500 でも、ここで例外にして呼び出し側に DOM を触らせない
async function veinReadJson(response) {
  let json = null;

  try {
    json = await response.json();
  } catch (e) {
    json = null;
  }

  if (!response.ok) {
    console.error(json);
    throw new Error((json && json.message) || '処理できませんでした。時間をおいて試してください。');
  }

  return json;
}
</script>

</body>
</html>
