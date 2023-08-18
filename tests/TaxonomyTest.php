<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\WP\Taxonomy
 */
final class TaxonomyTest extends TestCase
{

    public $Taxonomy;
    protected function setUp(): void
    {
        /** @var \IdeasOnPurpose\WP\Taxonomy $this->Taxonomy */
        $this->Taxonomy = $this->getMockForAbstractClass(WP\Taxonomy::class);
        $this->Taxonomy->slug = 'test';
        $this->Taxonomy->args = [];

        /**
         * Workaround the protected $css property.
         */
        $Ref = new \ReflectionClass($this->Taxonomy);
        $prop = $Ref->getProperty('css');
        $prop->setAccessible(true);
        $prop->setValue($this->Taxonomy, 'Test CSS String');
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
        $this->Taxonomy->adminStyles();
        $this->assertStringContainsString('wp-admin', $inline_styles[0]['handle']);
        $this->assertStringContainsString('/* START', $inline_styles[0]['data']);
    }
}
