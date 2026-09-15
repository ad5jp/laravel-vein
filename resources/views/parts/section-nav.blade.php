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
    position: sticky;
    /* 上のバーは fixed-top。その下に潜らせない */
    top: calc(var(--vein-navbar-height, 59px) + 1rem);
    max-height: calc(100vh - var(--vein-navbar-height, 59px) - 2rem);
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
    display: block;
    padding: 0.35rem 0 0.35rem 0.75rem;
    margin-left: -2px;
    border-left: 2px solid transparent;
    color: #495057;
    font-size: 0.875rem;
    text-decoration: none;
    line-height: 1.4;
}
.__section_nav--list a:hover { color: #0D6EFD; }
.__section_nav--list a.is-current {
    border-left-color: #0D6EFD;
    color: #0D6EFD;
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

        // いま画面の上端に一番近い見出しを現在地とする
        function mark() {
            let current = 0;

            sections.forEach((section, i) => {
                if (section.getBoundingClientRect().top <= 80) {
                    current = i;
                }
            });

            links.forEach((a, i) => a.classList.toggle('is-current', i === current));
        }

        mark();
        window.addEventListener('scroll', mark, { passive: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endif
