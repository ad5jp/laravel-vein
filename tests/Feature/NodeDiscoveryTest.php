<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Navigation\NavigationManager;
use AD5jp\Vein\Node\NodeManager;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\Fixtures\TestUser;
use AD5jp\Vein\Tests\TestCase;

/**
 * Node の探索が、モデルと同じディレクトリに置かれた
 * abstract クラス・enum・その他のクラスで壊れないこと。
 */
class NodeDiscoveryTest extends TestCase
{
    public function test_メニューに出るのは_node_だけ(): void
    {
        $navs = (new NavigationManager)->generate();

        $labels = array_map(fn ($nav) => $nav->label, $navs);
        sort($labels);

        $this->assertSame(['TestEntry', 'TestSoftEntry'], $labels);
    }

    public function test_abstract_と_enum_が同居していても管理画面が開ける(): void
    {
        $admin = TestUser::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        // Fixtures には AbstractNode（abstract）・TestStatus（enum）・
        // NeedsArgument（引数の要るコンストラクタ）が同居している
        $this->actingAs($admin)
            ->get('/'.config('vein.admin_uri'))
            ->assertOk();
    }

    public function test_名前空間の先頭バックスラッシュが無くても動く(): void
    {
        config()->set('vein.model_namespaces', ['AD5jp\\Vein\\Tests\\Fixtures']);

        $navs = (new NavigationManager)->generate();

        $this->assertCount(2, $navs);
    }

    public function test_node_でないクラスは解決されない(): void
    {
        $manager = new NodeManager;

        $this->assertInstanceOf(TestEntry::class, $manager->resolve('test_entry'));
        $this->assertNull($manager->resolve('abstract_node'));
        $this->assertNull($manager->resolve('test_status'));
        $this->assertNull($manager->resolve('needs_argument'));
        $this->assertNull($manager->resolve('no_such_model'));
    }
}
