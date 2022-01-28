<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
use WP_Taxonomy;
use WP_Post_Type;

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
 * @covers \IdeasOnPurpose\WP\Error
 */
final class DataModelLabelTest extends TestCase
{
    protected function setUp(): void
    {
        $this->DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        unset($GLOBALS['get_terms']);
        unset($GLOBALS['typenow']);
    }

    public function testUpdateLabels()
    {
        global $wp_post_types, $wp_taxonomies;

        $wp_post_types['page'] = new WP_Post_Type('page', 'Pages');
        $wp_taxonomies['category'] = new WP_Taxonomy('category');
        $wp_taxonomies['post_tag'] = new WP_Taxonomy('post_tag');

        $wp_post_types['page']->labels->titleCaseSingular = 'Titlecase Page';
        $wp_post_types['page']->labels->titleCasePlural = 'Titlecase Pages';
        $wp_post_types['page']->labels->lowercaseSingular = 'The label is page, see?';
        $wp_post_types['page']->labels->lowercasePlural = 'The label is pages, see?';

        $wp_taxonomies['category']->labels->titleCaseSingular = 'Titlecase Category';
        $wp_taxonomies['category']->labels->titleCasePlural = 'Titlecase Categories';
        $wp_taxonomies['category']->labels->lowercaseSingular = 'The label is category, see?';
        $wp_taxonomies['category']->labels->lowercasePlural = 'The label is categories, see?';

        $actual = $this->DataModel->labels('word');
        $this->assertStringContainsString('word', $actual->lowercaseSingular);
        $this->assertStringContainsString('Words', $actual->titleCasePlural);

        $actual = $this->DataModel->labels('thing', false, [], 'category');
        $this->assertStringContainsString('thing', $actual->lowercaseSingular);
        $this->assertStringContainsString('Thing', $actual->titleCasePlural);
    }

    public function testUpdateLabels_failNoKnownType()
    {
        global $wp_post_types, $wp_taxonomies;

        $wp_post_types['page'] = new WP_Post_Type('page', 'Pages');
        $wp_taxonomies['category'] = new WP_Taxonomy('category');
        $wp_taxonomies['post_tag'] = new WP_Taxonomy('post_tag');

        $noType = 'not-a-type';
        $actual = $this->DataModel->labels('thing', false, [], $noType);

        $this->assertStringContainsString('renaming failed', $actual->msg);
        $this->assertStringContainsString('not a known', $actual->msg);
        $this->assertStringContainsString($noType, $actual->msg);
    }

    public function testUpdateLabels_nullLabel()
    {
        global $wp_post_types;

        $wp_post_types['page'] = new WP_Post_Type('page', 'Pages');
        $wp_post_types['page']->labels->nullLabel = null;

        $actual = $this->DataModel->labels('thing', false, [], 'page');
        $this->assertNull($actual->nullLabel);
    }

    public function testUpdateLabels_override()
    {
        global $wp_post_types;

        $wp_post_types['page'] = new WP_Post_Type('page', 'Pages');
        $wp_post_types['page']->labels->test = 'Test Pages label';
        $wp_post_types['page']->labels->override = 'This page label will be gone';

        $overrideLabel = 'The new page label';

        $actual = $this->DataModel->labels('thing', true, ['override' => $overrideLabel], 'page');
        $this->assertEquals($actual->override, $overrideLabel);
        $this->assertStringContainsString('Things', $actual->test);
        $this->assertStringContainsString('Test', $actual->test);
    }

    public function testPostTypeLabels()
    {
        global $wp_post_types;

        $wp_post_types['page'] = new WP_Post_Type('page', 'Pages');
        $wp_post_types['page']->labels->testLabel = 'Some Pages label';
        $actual = $this->DataModel->postTypeLabels('dog');
        $this->assertStringContainsString('Dogs', $actual->testLabel);
    }

    public function testTaxonomyLabels()
    {
        global $wp_post_types, $wp_taxonomies;

        $wp_post_types['page'] = new WP_Post_Type('page', 'Pages');
        $wp_taxonomies['category'] = new WP_Taxonomy('category');
        $wp_taxonomies['post_tag'] = new WP_Taxonomy('post_tag');

        $wp_taxonomies['category']->labels->testLabel = 'Some Categories label';

        $actual = $this->DataModel->taxonomyLabels('color');
        $this->assertStringContainsString('Colors', $actual->testLabel);
    }
}
