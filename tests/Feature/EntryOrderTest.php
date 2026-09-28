<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestSortableEntry;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

/**
 * Entry の並べ替え。
 *
 * 一覧はページ送り（20 件）と絞り込みがあり、ページをまたいで動かせない。
 * 並べ替えは全件を並べた専用の画面で行い、グループ（ページごとの実績のページ等）の中だけで動かす。
 */
class EntryOrderTest extends TestCase
{
    private function admin(): TestUser
    {
        return TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);
    }

    private function adminUri(): string
    {
        return '/'.config('vein.admin_uri');
    }

    private function make(string $title, string $group, int $order): TestSortableEntry
    {
        return TestSortableEntry::create(['title' => $title, 'group' => $group, 'sort_order' => $order]);
    }

    public function test_並べ替えの画面には全件がグループごとに出る(): void
    {
        // 1 ページ（20 件）を超えてもページ送りしない
        foreach (range(1, 21) as $i) {
            $this->make("A{$i}", 'a', $i);
        }
        $this->make('B1', 'b', 1);

        $html = $this->actingAs($this->admin())
            ->get($this->adminUri().'/test_sortable_entry/order')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('グループ A', $html);
        $this->assertStringContainsString('グループ B', $html);
        $this->assertStringContainsString('>A21<', $html);
        $this->assertStringContainsString('>B1<', $html);
        $this->assertLessThan(strpos($html, 'グループ B'), strpos($html, '>A21<'), 'A の行は A の見出しの下に並ぶ');
    }

    public function test_並べ替えるとそのグループの並び順だけが入る(): void
    {
        $a1 = $this->make('A1', 'a', 5);
        $a2 = $this->make('A2', 'a', 6);
        $b1 = $this->make('B1', 'b', 7);

        $this->actingAs($this->admin())
            ->postJson($this->adminUri().'/test_sortable_entry/sort', ['ids' => "{$a2->id},{$a1->id}"])
            ->assertOk();

        $this->assertSame(0, (int) $a2->fresh()->sort_order);
        $this->assertSame(1, (int) $a1->fresh()->sort_order);
        $this->assertSame(7, (int) $b1->fresh()->sort_order, '別のグループは変わらない');
    }

    public function test_別のグループが混ざった並べ替えは弾かれる(): void
    {
        $a1 = $this->make('A1', 'a', 5);
        $b1 = $this->make('B1', 'b', 7);

        $this->actingAs($this->admin())
            ->postJson($this->adminUri().'/test_sortable_entry/sort', ['ids' => "{$b1->id},{$a1->id}"])
            ->assertStatus(422);

        $this->assertSame(5, (int) $a1->fresh()->sort_order);
        $this->assertSame(7, (int) $b1->fresh()->sort_order);
    }

    public function test_追加すると同じグループの末尾に入る(): void
    {
        $this->make('A1', 'a', 3);
        $this->make('B1', 'b', 9);

        $this->actingAs($this->admin())
            ->post($this->adminUri().'/test_sortable_entry/add', ['title' => 'A2', 'group' => 'a'])
            ->assertRedirect();

        $this->assertSame(4, (int) TestSortableEntry::query()->where('title', 'A2')->value('sort_order'));
    }

    public function test_一覧は並び順で並び並べ替えへの入口がある(): void
    {
        $this->make('二番', 'a', 2);
        $this->make('一番', 'a', 1);

        $html = $this->actingAs($this->admin())
            ->get($this->adminUri().'/test_sortable_entry')
            ->assertOk()
            ->getContent();

        $this->assertLessThan(strpos($html, '二番'), strpos($html, '一番'), '並び順で並ぶ');
        $this->assertStringContainsString($this->adminUri().'/test_sortable_entry/order', $html);
    }

    public function test_並べ替えを宣言していないノードには出ない(): void
    {
        TestEntry::create(['title' => '普通']);
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get($this->adminUri().'/test_entry')->assertOk()->getContent();
        $this->assertStringNotContainsString('/test_entry/order', $html);

        $this->actingAs($admin)->get($this->adminUri().'/test_entry/order')->assertNotFound();
        $this->actingAs($admin)->postJson($this->adminUri().'/test_entry/sort', ['ids' => '1'])->assertNotFound();
    }
}
