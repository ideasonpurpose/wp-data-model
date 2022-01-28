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
 * @covers \IdeasOnPurpose\WP\DataModel
 */
final class DataModelTest extends TestCase
{
    protected function setUp(): void
    {
        unset($GLOBALS['get_terms']);
        unset($GLOBALS['typenow']);
    }

    public function testParseTaxonomyMap()
    {
        global $register_taxonomy_for_object_type;

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $DataModel->taxonomyMap = ['test' => ['type0', 'type1']];
        $DataModel->parseTaxonomyMap();

        $this->assertCount('2', $register_taxonomy_for_object_type);
        $this->assertContains('type0', $register_taxonomy_for_object_type[0]);
        $this->assertContains('type1', $register_taxonomy_for_object_type[1]);
    }

    public function testParseTaxonomyFilterMap_matchTypesArray()
    {
        global $typenow;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['injectTaxonomyFilterMenu', 'register'])
            ->getMock();

        $tax = 'Test-Tax';
        $type = 'Test-Type';
        $typenow = $type;

        $DataModel
            ->expects($this->once())
            ->method('injectTaxonomyFilterMenu')
            ->with($tax);

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = [$tax => ['type0', $type]];
        $DataModel->parseTaxonomyFilterMap();
    }

    public function testParseTaxonomyFilterMap_matchTypesString()
    {
        global $typenow;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['injectTaxonomyFilterMenu', 'register'])
            ->getMock();

        $tax = 'Test-Tax';
        $type = 'Test-Type';
        $typenow = $type;

        $DataModel
            ->expects($this->once())
            ->method('injectTaxonomyFilterMenu')
            ->with($tax);

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = [$tax => $type];
        $DataModel->parseTaxonomyFilterMap();
    }

    public function testParseTaxonomyFilterMap_mismatchTypesString()
    {
        global $typenow;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['injectTaxonomyFilterMenu', 'register'])
            ->getMock();

        $tax = 'Test-Tax';
        $type = 'Test-Type';
        $typenow = $type;

        $DataModel->expects($this->never())->method('injectTaxonomyFilterMenu');

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = [$tax => 'frog'];
        $DataModel->parseTaxonomyFilterMap();
    }

    public function testParseTaxonomyFilterMap_noTypeNowMatch()
    {
        global $typenow;
        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['injectTaxonomyFilterMenu', 'register'])
            ->getMock();

        $DataModel->expects($this->never())->method('injectTaxonomyFilterMenu');

        $typenow = 'bird';

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = ['dog' => 'Stella'];
        $DataModel->parseTaxonomyFilterMap();
        $this->expectOutputString('');
    }

    public function testParseTaxonomyFilterMap_noTypeNow()
    {
        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['injectTaxonomyFilterMenu', 'register'])
            ->getMock();

        $DataModel->expects($this->never())->method('injectTaxonomyFilterMenu');

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->parseTaxonomyFilterMap();
        $this->expectOutputString('');
    }

    public function testInjectTaxonomyFilterMenu()
    {
        global $taxonomies;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $tax_key = 'flavor';
        $taxonomies = [$tax_key => new WP_Taxonomy()];

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->injectTaxonomyFilterMenu($tax_key);

        $actual = $this->getActualOutput();
        $this->assertStringContainsString('<select', $actual);
        $this->assertStringContainsString('<option', $actual);
        $this->assertStringNotContainsString('disabled', $actual);
        $this->expectOutputRegex('/\/option>\s+<option /');
    }

    public function testInjectTaxonomyFilterMenu_selected()
    {
        global $taxonomies;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $tax_key = 'flavor';
        $taxonomies = [$tax_key => new WP_Taxonomy()];

        $mockTerms = get_terms($tax_key);
        $_GET[$tax_key] = $mockTerms[0]->slug;

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->injectTaxonomyFilterMenu($tax_key);
        $this->expectOutputRegex('/selected="selected"/');
    }

    public function testInjectTaxonomyFilterMenu_notSelected()
    {
        global $taxonomies;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $tax_key = 'flavor';
        $taxonomies = [$tax_key => new WP_Taxonomy()];

        $_GET[$tax_key] = 'not-a-match';

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->injectTaxonomyFilterMenu($tax_key);

        $actual = $this->getActualOutput();
        $this->assertStringNotContainsString('selected', $actual);
        $this->expectOutputRegex('/<select/');
    }

    public function testInjectTaxonomyFilterMenu_noTerms()
    {
        global $taxonomies, $get_terms;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $tax_key = 'flavor';
        $taxonomies = [$tax_key => new WP_Taxonomy()];
        $no_terms_label = $taxonomies[$tax_key]->labels->no_terms;

        $get_terms = [];

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->injectTaxonomyFilterMenu($tax_key);

        $actual = $this->getActualOutput();
        $this->assertStringContainsString($no_terms_label, $actual);
        $this->assertStringContainsString('disabled', $actual);
        $this->expectOutputRegex('/<select/');
    }

    public function testInjectTaxonomyFilterMenu_noTaxonomy()
    {
        global $taxonomies;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $taxonomies = ['a' => 'b'];

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $actual = $DataModel->injectTaxonomyFilterMenu('c');

        $this->assertNull($actual);
        $this->expectOutputString('');
    }
}
