<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * Empty class for mocking the abstract class
 */
class CPTMock extends CPT
{
    public function props() {}
}

#[CoversClass(\IdeasOnPurpose\WP\CPT::class)]
#[CoversClass(\IdeasOnPurpose\WP\DataModel::class)]
#[CoversClass(\IdeasOnPurpose\WP\Error::class)]
final class CPTTest extends TestCase
{
    public $CPT;
    public $Ref;
    public $css;

    protected function setUp(): void
    {
        $reflection = new \ReflectionClass(CPTMock::class);
        $this->CPT = $reflection->newInstanceWithoutConstructor();
        $this->CPT->type = 'test_post_type';
        $this->CPT->args = [];
        $this->CPT->css = '';
    }

    public function test__construct()
    {
        $mockCPT = $this->getMockBuilder(CPTMock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['props'])
            ->getMock();

        $mockCPT->expects($this->once())->method('props');

        $mockCPT->__construct(123);
        $this->assertEquals(123, $mockCPT->menu_index);
    }

    public function testRegister()
    {
        global $post_types;
        $post_types = [];
        $this->assertNull($this->CPT->register());
        $this->assertContains($this->CPT->type, $post_types);
    }

    public function testAdminStyles()
    {
        global $inline_styles;
        $inline_styles = [];

        $this->CPT->css = 'Test CSS String';

        $this->CPT->adminStyles();
        $this->assertStringContainsString('wp-admin', $inline_styles[0]['handle']);
        $this->assertStringContainsString('/* START', $inline_styles[0]['data']);
    }

    public function testRemoveDateMenu()
    {
        $actual = $this->CPT->removeDateMenu(false, $this->CPT->type);
        $this->assertTrue($actual);

        $actual = $this->CPT->removeDateMenu(false, 'frog');
        $this->assertFalse($actual);
    }

    public function testFilterByTaxonomy_DeprecationNotice()
    {
        global $actions;
        $actions = [];

        $this->CPT->filterByTaxonomy('topic');

        $errors = all_added_actions();

        $this->expectOutputString('');
        $this->assertCount(1, $actions);
        $this->assertContains('wp_head', $errors[0]);
        $this->assertStringContainsString('moved to DataModel', $actions[0]['action'][0]->msg);
    }
}
