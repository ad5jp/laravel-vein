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
    <meta name="csrf" content="{{ csrf_token() }}">
</head>
<body>

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
          <a class="sidenav-link" aria-current="page" href="{{ route('vein.home') }}">ダッシュボード</a>
        </li>
        @foreach ($navs as $nav)
        <li class="sidenav-item">
          <a class="sidenav-link" href="{{ $nav->link }}">{{ $nav->label }}</a>
        </li>
        @endforeach
      </ul>
    </nav>
  </div>
  @endauth
  <main class="main">
    @yield('content')
  </main>
</div>

<script>
function toggleSidebar() {
  $('body').toggleClass('md-sidebar-hide').toggleClass('sm-sidebar-show')
}
</script>

</body>
</html>
