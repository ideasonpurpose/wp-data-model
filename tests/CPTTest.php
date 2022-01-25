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

    public function testFilterByTaxonomy()
    {
        global $typenow, $taxonomies;
        $typenow = $this->CPT->type;
        $taxonomies['topic'] = new WP_Taxonomy();
        $this->CPT->filterByTaxonomy('topic');
        $actual = $this->getActualOutput();
        $this->assertStringContainsString('<select', $actual);
        $this->assertStringContainsString('<option', $actual);
        $this->assertStringNotContainsString('disabled', $actual);
        $this->expectOutputRegex('/\/option>\s+<option /');
    }

    public function testFilterByTaxonomySelected()
    {
        global $typenow, $taxonomies;
        $typenow = $this->CPT->type;
        $taxonomies['topic'] = new WP_Taxonomy();
        $mockTerms = get_terms('topic');
        $_GET['topic'] = $mockTerms[0]->slug;
        $this->CPT->filterByTaxonomy('topic');
        $this->expectOutputRegex('/selected="selected"/');
    }

    public function testFilterByTaxonomy_NoTerms()
    {
        global $typenow, $taxonomies, $get_terms;
        $typenow = $this->CPT->type;
        $taxonomies['topic'] = new WP_Taxonomy();
        $no_terms_label = $taxonomies['topic']->labels->no_terms;
        $get_terms = [];
        $this->CPT->filterByTaxonomy('topic');
        $actual = $this->getActualOutput();
        $this->assertStringContainsString($no_terms_label, $actual);
        $this->assertStringContainsString('disabled', $actual);
        $this->expectOutputRegex('/<select/');
        unset($GLOBALS['get_terms']);
    }

    public function testFilterByTaxonomy_NoTypenow()
    {
        unset($GLOBALS['typenow']);
        $this->CPT->filterByTaxonomy('topic1');
        $this->expectOutputString('');
    }

    public function testFilterByTaxonomy_NoTypenowMatch()
    {
        global $typenow;
        $typenow = 'bird';
        $this->CPT->filterByTaxonomy('topic2');
        $this->expectOutputString('');
    }

    public function testFilterByTaxonomy_NoTaxonomy()
    {
        global $typenow;
        $typenow = $this->CPT->type;
        $actual = $this->CPT->filterByTaxonomy('topic3');
        $this->expectOutputString('');
        $this->assertNull($actual);
    }

    public function testFilterByTaxonomy_DeprecationNotice()
    {
        global $typenow, $actions;
        $actions = [];
        $typenow = $this->CPT->type;
        $taxonomies['topic'] = new WP_Taxonomy();
        $this->CPT->filterByTaxonomy('topic');
        $actual = $this->getActualOutput();
        $errors = all_added_actions();

        $this->expectOutputRegex('/selected="selected"/');
        $this->assertStringContainsString('<select', $actual);
        $this->assertStringContainsString('<option', $actual);
        $this->assertCount(1, $actions);
        $this->assertContains('wp_head', $errors[0]);
        $this->assertStringContainsString('deprecated', $actions[0]['action'][0]->msg);
    }
}
