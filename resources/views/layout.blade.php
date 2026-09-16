<!DOCTYPE html>
<html lang="ja">
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
    .__has_error .form-select { border-color: #DC3545; }
    /* ファイルを選ぶ欄は枠を持たない。色を指定しても何も描かれないので、
       外側に線を引く */
    .__has_error .__uploader_input { outline: 2px solid #DC3545; outline-offset: 2px; }
    .__has_error .form-label { color: #DC3545; }
    .__field_error { color: #DC3545; font-size: 0.875em; margin: 0.25rem 0 0; }
    .__field_hint { color: #6C757D; font-size: 0.8125rem; margin: 0.25rem 0 0; }
    /* 弾かれたときこそ、字数などの決まりを見ながら直す。理由と並べて残す */
    .__has_error .__field_hint { color: #6C757D; }

    /* 見出しと「一覧に戻る」。入力欄が長い画面では、スクロールすると戻る手立てが
       画面の外に出てしまうため貼り付ける。背景を敷かないと下の内容が透ける */
    .__page_head {
        position: sticky;
        top: var(--vein-navbar-height);
        z-index: 9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        /* .container の左右の余白を打ち消して、背景を端まで伸ばす */
        margin: 0 calc(var(--bs-gutter-x, 1.5rem) * -0.5) 1rem;
        padding: 0.75rem calc(var(--bs-gutter-x, 1.5rem) * 0.5);
        background: #FFF5F3;
        border-bottom: 1px solid #F0E3E0;
    }
    /* 型番のような折り返せない語が入ると、見出しが縮まず画面ごと横に流れる。
       縮むことを許し、収まらない語は途中で折る */
    .__page_head > :first-child { min-width: 0; overflow-wrap: anywhere; }

    /* 一覧画面など、まだ貼り付ける作りになっていない画面でも横並びは崩さない */
    .no-sidebar .main { width: 100%; }

    /* 本体と目次を横に並べる。
       min-width: 0 が無いと、中に幅の広いものがあるときに本体が縮まず、目次が画面の外へ出る */
    .__edit_body { display: flex; align-items: flex-start; }
    .__edit_body > .section { flex: 1 1 auto; min-width: 0; }

    /* 入力欄の区切り（@see src/Form/Input/Section.php） */
    .__form_section {
        margin: 2.5rem 0 1.25rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #DEE2E6;
    }
    .__form_section:first-of-type { margin-top: 0; }
    .__form_section--title { margin: 0; font-size: 1.05rem; font-weight: 700; color: #495057; }
    .__form_section--note { margin: 0.25rem 0 0; font-size: 0.875rem; color: #6C757D; }

    /* 上のバーは fixed-top で画面に貼り付いている。その下に潜らないよう、
       貼り付ける位置の基準をここに置く */
    :root {
        /* 上のバーの高さ。中身で変わるので JS が実測して上書きする。
           ここの値は、実測が届くまでの初期値 */
        --vein-navbar-height: 59px;
        /* 見出しの行の高さ。同じく JS が実測して上書きする */
        --vein-page-head-height: 0px;
    }
    /* 上のバーは画面に貼り付いているので、本文をその分だけ下げる。
       別ファイルにも同じ意味の指定があるが、あちらは固定値で実測に追従しない */
    body { padding-top: var(--vein-navbar-height); }
    /* 上のバーと見出しの行を足した、画面上部で隠れる高さ */
    :root { --vein-offset-top: calc(var(--vein-navbar-height) + var(--vein-page-head-height)); }

    /* いま触っている欄が分かるようにする。Bootstrap の既定は、この画面の色だと
       白地に 1.34:1 でほとんど見えない。数十の欄を順に送ると行方が分からなくなる */
    .form-control:focus,
    .form-select:focus,
    .__uploader_input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.45);
    }
    /* 弾かれた欄は、直しに行っても赤のままにする。触った瞬間に
       「ここが間違っている」印が消えると、どこを直すのか分からなくなる */
    .__has_error .form-control:focus,
    .__has_error .form-select:focus {
        border-color: #DC3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.4);
    }

    /* 画面の中へ運ばれるとき（目次からのジャンプ、Tab での移動、ページ内検索）に、
       上で隠れている分だけ手前で止める。1rem は、貼り付いた見出しにぴったり
       着けず、少し離して見せるための余白。
       欄ごとの scroll-margin-top では新しく増えた移動手段を取りこぼすうえ、
       両方書くと足し算になって余計に下がる */
    html { scroll-padding-top: calc(var(--vein-offset-top) + 1rem); }

    /* メニューは見出しの行より手前に置く。見出しの行を貼り付けた際に重なり順を
       与えたため、番号を持たないメニューが下になり、狭い画面では開いても
       見出しの行に覆われて 1 つも押せなくなっていた */
    .sidebar { z-index: 11; }

    /* 入力欄が長い画面では、スクロールするとメニューが画面の外へ出てしまう。
       貼り付けておき、項目が多いときはメニュー側だけスクロールさせる */
    @media (min-width: 768px) {
        .sidebar {
            position: sticky;
            top: var(--vein-navbar-height);
            align-self: flex-start;
            height: calc(100vh - var(--vein-navbar-height));
            max-height: none;
            overflow-y: auto;
        }
        /* 貼り付けた要素の left は移動量ではなく、貼り付く位置の制約になる。
           admin.scss の left: -200px はここでは動かす力を持たず、左端に引き戻す
           働きしかしない。打ち消したうえで、畳むのは余白で行う */
        .md-sidebar-hide .sidebar { left: auto; margin-left: -200px; margin-right: 0; }
    }
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

// 上のバーと見出しの行の高さは、中身でも画面の幅でも変わる。貼り付けた目次や
// 見出しへのジャンプがその下に潜らないよう、実測して配る
$(function () {
  const navbar = document.querySelector('.navbar.fixed-top');
  const head = document.querySelector('.__page_head');

  const apply = () => {
    const style = document.documentElement.style;

    if (navbar) {
      style.setProperty('--vein-navbar-height', navbar.offsetHeight + 'px');
    }

    if (head) {
      style.setProperty('--vein-page-head-height', head.offsetHeight + 'px');
    }
  };

  apply();
  window.addEventListener('resize', apply);

  // メニューの開け閉めでも本文の幅が変わり、見出しが折り返す行数が変わる。
  // 幅の変化は resize では拾えないので、動きが終わってから測り直す
  document.addEventListener('vein:sidebar-toggled', () => setTimeout(apply, 600));
});

// 押したことが分かるようにする。画像を含む画面では往復に数秒かかり、
// 見た目が変わらないと二度押しになる
$(function () {
  $(document).on('submit', 'form', function () {
    // type を書いていないボタンも既定は送信。属性ではなく解決後の型で見る
    const $pressed = $(document.activeElement).closest('button')
      .filter(function () { return this.type === 'submit'; });
    const $button = $pressed.length
      ? $pressed
      : $(this).find('.__save_bar button').filter(function () { return this.type === 'submit'; }).last();

    if (!$button.length) {
      return;
    }

    // 文言は元のまま活かす（「更新」→「更新しています…」「登録」→「登録しています…」）。
    // 保存バーの外のボタン（検索など）は文言を変えず、二度押しだけ止める
    if ($button.closest('.__save_bar').length && $button.hasClass('btn-primary')) {
      $button.text($button.text().trim() + 'しています…');
    }

    // 送信が始まってから無効にする。同じ処理の中で無効にすると、
    // ブラウザによっては送信そのものが取り消される
    setTimeout(() => $button.prop('disabled', true), 0);
  });
});

// 打ちかけのまま画面を離れると、入力が黙って消える。
// 削除には確認があるのに、こちらには無かった
$(function () {
  // 保存バーを持つものが編集のフォーム。新規登録の画面も同じ作りなので拾える
  const bar = document.querySelector('.__save_bar');
  const form = bar ? bar.closest('form') : null;

  if (!form) {
    return;
  }

  let dirty = false;
  let leaving = false;

  const touched = () => { dirty = true; };

  form.addEventListener('input', touched);
  form.addEventListener('change', touched);
  // 打ち込む以外でも中身は変わる。行の増減・並べ替え・画像を外す、など
  document.addEventListener('vein:changed', touched);

  // 送信したなら、その先で保存されるか、確認を出したうえで消える。
  // 削除やログアウトも同じなので、どのフォームの送信でも黙って通す
  document.addEventListener('submit', () => { leaving = true; });

  window.addEventListener('beforeunload', (event) => {
    if (!dirty || leaving) {
      return;
    }

    event.preventDefault();
    event.returnValue = '';
  });
});

function toggleSidebar() {
  $('body').toggleClass('md-sidebar-hide').toggleClass('sm-sidebar-show')
  document.dispatchEvent(new CustomEvent('vein:sidebar-toggled'))
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
