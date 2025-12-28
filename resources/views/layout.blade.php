<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="{{ asset('vein-assets/admin.css') }}">
    <script src="{{ asset('vein-assets/bootstrap.js') }}"></script>
</head>
<body>

<nav class="navbar bg-dark border-bottom border-bottom-dark navbar-expand-md fixed-top" data-bs-theme="dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Vein</a>
    @auth
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" aria-current="page" href="{{ route('vein.home') }}">Home</a>
        </li>
        @foreach ($navs as $nav)
        <li class="nav-item">
          <a class="nav-link" href="{{ $nav->link }}">{{ $nav->label }}</a>
        </li>
        @endforeach
      </ul>
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

@yield('content')

</body>
</html>
