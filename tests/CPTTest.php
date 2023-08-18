<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
use WP_Taxonomy;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

/**
 * @covers \IdeasOnPurpose\WP\CPT
 * @covers \IdeasOnPurpose\WP\DataModel
 * @covers \IdeasOnPurpose\WP\Error
 */
final class CPTTest extends TestCase
{
    public $CPT;
    public $Ref;
    public $css;

    protected function setUp(): void
    {
        /** @var \IdeasOnPurpose\WP\CPT $this->Taxonomy */
        $this->CPT = $this->getMockForAbstractClass(CPT::class);
        $this->CPT->type = 'test_post_type';
        $this->CPT->args = [];

        /**
         * Workaround the protected $css property.
         */
        $this->Ref = new \ReflectionClass($this->CPT);
        $this->css = $this->Ref->getProperty('css');
        $this->css->setAccessible(true);
        $this->css->setValue($this->CPT, '');
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

        $this->css->setValue($this->CPT, 'Test CSS String');

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
