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
        $this->expectOutputRegex('/<select/');
        $this->expectOutputRegex('/<option/');
        $this->expectOutputRegex('/\/option><option /');
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

    public function testFilterByTaxonomyNoTypenow()
    {
        unset($GLOBALS['typenow']);
        $this->CPT->filterByTaxonomy('topic1');
        $this->expectOutputString('');
    }

    public function testFilterByTaxonomyNoTypenowMatch()
    {
        global $typenow;
        $typenow = 'bird';
        $this->CPT->filterByTaxonomy('topic2');
        $this->expectOutputString('');
    }

    public function testFilterByTaxonomyNoTaxonomy()
    {
        global $typenow;
        $typenow = $this->CPT->type;
        $actual = $this->CPT->filterByTaxonomy('topic3');
        $this->expectOutputString('');
        $this->assertNull($actual);
    }
}
