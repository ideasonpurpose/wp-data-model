<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * Empty class for mocking the abstract class
 */
class TaxonomyMock extends Taxonomy
{
    public function props() {}
}

#[CoversClass(\IdeasOnPurpose\WP\Taxonomy::class)]
final class TaxonomyTest extends TestCase
{
    public $Taxonomy;
    protected function setUp(): void
    {
        $reflection = new \ReflectionClass(TaxonomyMock::class);
        $this->Taxonomy = $reflection->newInstanceWithoutConstructor();
        $this->Taxonomy->args = [];
    }

    public function test__construct()
    {
        $mockTaxonomy = $this->getMockBuilder(TaxonomyMock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['props'])
            ->getMock();

        $mockTaxonomy->expects($this->once())->method('props');

        $types = ['color', 'animal'];
        $mockTaxonomy->__construct($types);
        $this->assertContains('color', $mockTaxonomy->post_types);
        $this->assertContains('animal', $mockTaxonomy->post_types);
    }
    public function testRegister()
    {
        global $taxonomies;
        $taxonomies = [];
        $this->assertNull($this->Taxonomy->register());
        $this->assertContains($this->Taxonomy->slug, $taxonomies);
    }

    public function testAdminStyles()
    {
        global $inline_styles;
        $inline_styles = [];
        $fakeRule = 'fake style rule';
        $this->Taxonomy->css = $fakeRule;
        $this->Taxonomy->adminStyles();
        $this->assertStringContainsString('wp-admin', $inline_styles[0]['handle']);
        $this->assertStringContainsString('/* START', $inline_styles[0]['data']);
        $this->assertStringContainsString('style rule', $inline_styles[0]['data']);
    }
}
