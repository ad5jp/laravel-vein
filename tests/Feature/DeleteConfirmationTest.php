<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;

/**
 * 削除の 3 経路すべてに確認が入っていること。
 *
 * confirm( はコードベース全体で 0 件で、どれも 1 クリックで効いていた。
 */
class DeleteConfirmationTest extends TestCase
{
    private function editHtml(): string
    {
        $admin = TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $entry = TestEntry::create(['title' => '親']);

        return (string) $this->actingAs($admin)
            ->get(sprintf('/%s/test_entry/%s', config('vein.admin_uri'), $entry->id))
            ->getContent();
    }

    private function addHtml(): string
    {
        $admin = TestUser::create([
            'name' => '管理者',
            'email' => 'add@example.com',
            'password' => 'secret',
        ]);

        return (string) $this->actingAs($admin)
            ->get(sprintf('/%s/test_entry/add', config('vein.admin_uri')))
            ->getContent();
    }

    public function test_確認のハンドラが各画面で読み込まれる(): void
    {
        $html = $this->editHtml();

        $this->assertStringContainsString('__confirm_delete', $html);
        $this->assertStringContainsString('削除すると元に戻せません。削除しますか？', $html);
    }

    public function test_確認は各画面のハンドラより先に束縛される(): void
    {
        $html = $this->editHtml();

        $confirm = strpos($html, "\$(document).on('click', '.__confirm_delete'");
        $content = strpos($html, '<main');

        $this->assertNotFalse($confirm);
        $this->assertNotFalse($content);
        $this->assertLessThan(
            $content,
            $confirm,
            '確認のハンドラが本文より後に束縛されると、各画面のハンドラが先に走る',
        );
    }

    public function test_entry_の削除ボタンに確認が付いている(): void
    {
        $html = $this->editHtml();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*__confirm_delete[^>]*>[^<]*削除/u',
            $html,
        );
    }

    /**
     * 削除は更新と同じ行に並べるが、フォームは別に置く。
     *
     * フォームは入れ子にできないため、同じ行に置くには form 属性で送るしかない。
     * ここが崩れると、削除を押しても更新が走る。
     */
    public function test_削除ボタンは更新のフォームの外へ送る(): void
    {
        $html = $this->editHtml();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*form="delete"[^>]*__confirm_delete[^>]*>[^<]*を削除<\/button>/u',
            $html,
        );
        $this->assertMatchesRegularExpression('/<form[^>]*id="delete"/u', $html);
    }

    /** 新規追加の画面には消す相手がいない。 */
    public function test_新規追加には削除ボタンが出ない(): void
    {
        $html = $this->addHtml();

        $this->assertStringContainsString('__save_bar', $html);
        $this->assertStringNotContainsString('を削除</button>', $html);
    }

    public function test_行削除にも確認が入っている(): void
    {
        $html = $this->editHtml();

        $this->assertStringContainsString('この行を削除しますか？', $html);
    }
}
