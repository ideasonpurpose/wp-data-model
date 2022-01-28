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
        unset($GLOBALS['taxonomies']);
        unset($GLOBALS['wp_dropdown_categories']);
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
        global $typenow, $taxonomies, $wp_dropdown_categories;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $tax = 'Test-Tax-MatchArray';
        $type = 'Test-Type-MatchArray';
        $typenow = $type;

        $wp_dropdown_categories = [];
        $taxonomies[$tax] = new WP_Taxonomy($tax);

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = [$tax => ['type0', $type]];
        $DataModel->parseTaxonomyFilterMap();

        $this->assertCount(1, $wp_dropdown_categories);
    }

    public function testParseTaxonomyFilterMap_matchTypesString()
    {
        global $typenow, $taxonomies, $wp_dropdown_categories;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $tax = 'Test-Tax-MatchString';
        $type = 'Test-Type-MatchString';
        $typenow = $type;

        $wp_dropdown_categories = [];
        $taxonomies[$tax] = new WP_Taxonomy($tax);

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = [$tax => $type];
        $DataModel->parseTaxonomyFilterMap();

        $this->assertCount(1, $wp_dropdown_categories);
    }

    public function testParseTaxonomyFilterMap_mismatchTypesString()
    {
        global $typenow, $wp_dropdown_categories;

        $wp_dropdown_categories = [];

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $tax = 'Test-Tax';
        $type = 'Test-Type';
        $typenow = $type;

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = [$tax => 'frog'];
        $DataModel->parseTaxonomyFilterMap();

        $this->assertCount(0, $wp_dropdown_categories);
    }

    public function testParseTaxonomyFilterMap_noTypeNowMatch()
    {
        global $typenow, $wp_dropdown_categories;

        $wp_dropdown_categories = [];

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $typenow = 'bird';

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = ['dog' => 'Stella'];
        $DataModel->parseTaxonomyFilterMap();
        $this->assertCount(0, $wp_dropdown_categories);
    }

    public function testParseTaxonomyFilterMap_noTypeNow()
    {
        global $wp_dropdown_categories;

        $wp_dropdown_categories = [];

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->parseTaxonomyFilterMap();
        $this->expectOutputString('');

        $this->assertCount(0, $wp_dropdown_categories);
    }
}
