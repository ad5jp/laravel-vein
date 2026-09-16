<?php
/**
 * 見出しから作る目次。
 *
 * 入力欄が多い画面では、目的の欄まで運ぶ手立てが要る。Section を 2 つ以上置いている
 * ノードでだけ出す。
 *
 * @see src/Form/Input/Section.php
 */
?>@php
    $sections = array_values(array_filter(
        $editFields,
        fn ($field) => $field instanceof \AD5jp\Vein\Form\Input\Section,
    ));
@endphp

@if (count($sections) >= 2)
<style>
.__section_nav {
    /* 目次は「欄まで運ぶ」ためのもの。読み上げやキーボードでも先に届くよう
       並びの先頭に置き、見た目だけを右へ寄せる */
    order: 1;
    position: sticky;
    /* 上のバーと見出しの行の下に潜らせない */
    top: calc(var(--vein-offset-top) + 1rem);
    max-height: calc(100vh - var(--vein-offset-top) - 2rem);
    overflow-y: auto;
    align-self: flex-start;
    width: 13rem;
    flex: none;
    margin-left: 1.5rem;
}
.__section_nav--title {
    margin: 0 0 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    color: #6C757D;
}
.__section_nav--list { margin: 0; padding: 0; list-style: none; border-left: 2px solid #DEE2E6; }
.__section_nav--list a {
    overflow-wrap: anywhere;
    display: block;
    padding: 0.35rem 0 0.35rem 0.75rem;
    margin-left: -2px;
    border-left: 2px solid transparent;
    color: #495057;
    font-size: 0.875rem;
    text-decoration: none;
    line-height: 1.4;
}
.__section_nav--list a:hover { color: var(--bs-primary); }
.__section_nav--list a.is-current {
    border-left-color: var(--bs-primary);
    color: var(--bs-primary);
    font-weight: 700;
}
/* 画面が狭いときは横に置く余地がない */
@media (max-width: 991px) { .__section_nav { display: none; } }
</style>
<nav class="__section_nav" aria-label="入力欄の目次">
    <p class="__section_nav--title">入力欄</p>
    <ul class="__section_nav--list">
        @foreach ($sections as $section)
        <li><a href="#{{ $section->anchor() }}">{{ $section->label }}</a></li>
        @endforeach
    </ul>
</nav>
<script>
(function () {
    'use strict';

    function init() {
        const links = [...document.querySelectorAll('.__section_nav--list a')];
        const sections = links
            .map((a) => document.getElementById(a.getAttribute('href').slice(1)))
            .filter(Boolean);

        if (sections.length === 0) {
            return;
        }

        // 画面の上端は、上のバーと見出しの行に隠れている。ここを固定値にすると、
        // 隠れて見えない見出しが現在地のままになる
        // （@see resources/views/layout.blade.php の高さの実測）
        const navbar = document.querySelector('.navbar.fixed-top');
        const pageHead = document.querySelector('.__page_head');
        let line = 0;

        function measure() {
            // 見出しへ飛んだときの余白（scroll-margin-top の 1rem）を少し超える値にする。
            // ちょうどにすると、飛んだ直後にその見出しが現在地から外れる
            line = (navbar ? navbar.offsetHeight : 0)
                + (pageHead ? pageHead.offsetHeight : 0)
                + 20;
        }

        function atBottom() {
            return window.innerHeight + window.scrollY >= document.body.scrollHeight - 2;
        }

        // 押した項目。末尾に近い見出しへ飛ぶと、それ以上スクロールできないぶん
        // 判定が最後の見出しを指してしまい、押したものと食い違う。
        // 飛んだ直後だけは、押したものを現在地として扱う
        let picked = null;
        let pickedUntil = 0;

        links.forEach((link, i) => link.addEventListener('click', () => {
            picked = i;
            pickedUntil = Date.now() + 1500;
            // 末尾まで来ていると、押しても画面が動かず scroll が飛ばない。
            // その場で塗り直す
            mark();
        }));

        // いま画面の上端に一番近い見出しを現在地とする
        function mark() {
            if (picked !== null && Date.now() < pickedUntil) {
                links.forEach((a, i) => a.classList.toggle('is-current', i === picked));

                return;
            }

            picked = null;

            let current = 0;

            sections.forEach((section, i) => {
                if (section.getBoundingClientRect().top <= line) {
                    current = i;
                }
            });

            // 最後の見出しは、これ以上スクロールできないため上端まで来ない。
            // 末尾まで運んだら最後を現在地にする
            if (atBottom()) {
                current = sections.length - 1;
            }

            links.forEach((a, i) => a.classList.toggle('is-current', i === current));
        }

        measure();
        mark();
        window.addEventListener('scroll', mark, { passive: true });
        window.addEventListener('resize', () => { measure(); mark(); });
        // メニューを開け閉めすると本文の幅が変わり、見出しの行数も変わる。
        // 幅の変化は resize では拾えないので、動きが終わってから測り直す
        document.addEventListener('vein:sidebar-toggled', () => setTimeout(() => { measure(); mark(); }, 600));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endif
