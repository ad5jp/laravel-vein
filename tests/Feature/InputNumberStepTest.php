<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Feature;

use AD5jp\Vein\Form\Input\InputNumber;
use AD5jp\Vein\Tests\Fixtures\TestEntry;
use AD5jp\Vein\Tests\TestCase;
use Exception;

/**
 * step を省略した <input type="number"> はブラウザ既定の step="1" が効き、
 * 小数を入力するとフォームの送信自体が弾かれる。
 */
class InputNumberStepTest extends TestCase
{
    public function test_省略時は従来どおり_step_が出ない(): void
    {
        $entry = TestEntry::create(['title' => '15']);
        $html = (new InputNumber(key: 'title'))->renderInline($entry);

        $this->assertStringNotContainsString('step=', $html);
        $this->assertSame('<input type="number" name="title" value="15" class="form-control">', $html);
    }

    public function test_小数の_step_が描画される(): void
    {
        $entry = TestEntry::create(['title' => '15.5']);
        $html = (new InputNumber(key: 'title', step: 0.1))->renderInline($entry);

        $this->assertStringContainsString('step="0.1"', $html);
        $this->assertSame('<input type="number" step="0.1" name="title" value="15.5" class="form-control">', $html);
    }

    public function test_any_を指定できる(): void
    {
        $entry = TestEntry::create(['title' => '1']);
        $html = (new InputNumber(key: 'title', step: 'any'))->renderInline($entry);

        $this->assertStringContainsString('step="any"', $html);
    }

    public function test_整数と数値文字列も指定できる(): void
    {
        $entry = TestEntry::create(['title' => '1']);

        $this->assertStringContainsString('step="5"', (new InputNumber(key: 'title', step: 5))->renderInline($entry));
        $this->assertStringContainsString('step="0.5"', (new InputNumber(key: 'title', step: '0.5'))->renderInline($entry));
    }

    public function test_正の数でも_any_でもない値は例外(): void
    {
        $this->expectException(Exception::class);
        new InputNumber(key: 'title', step: 'あいまい');
    }

    public function test_ゼロや負の数は例外(): void
    {
        $this->expectException(Exception::class);
        new InputNumber(key: 'title', step: 0);
    }

    public function test_既存の引数を位置で渡しても壊れない(): void
    {
        $entry = TestEntry::create(['title' => '7']);
        $html = (new InputNumber('title', 'ラベル'))->renderInline($entry);

        $this->assertStringContainsString('value="7"', $html);
    }
}
