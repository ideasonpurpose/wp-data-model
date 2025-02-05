<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

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

#[CoversClass(\IdeasOnPurpose\WP\DataModel::class)]
#[CoversClass(\IdeasOnPurpose\WP\Labels::class)]
#[CoversClass(\IdeasOnPurpose\WP\Error::class)]
final class DataModelLabelTest extends TestCase
{
    public $DataModel;
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
        $actual =  $this->DataModel->labels('word');
        $this->assertEqualsIgnoringCase('word', $actual['singular_name']);
        $this->assertEqualsIgnoringCase('Words', $actual['name']);

        $actual = $this->DataModel->labels('thing', false, [], 'category');
        $this->assertEqualsIgnoringCase('thing', $actual['singular_name']);
        $this->assertEqualsIgnoringCase('Thing', $actual['name']);
    }

    public function testUpdateLabels_failNoKnownType()
    {
        global $wp_post_types, $wp_taxonomies;

        $wp_post_types['page'] = new WP_Post_Type('page', 'Pages');
        $wp_taxonomies['category'] = new WP_Taxonomy('category', 'post');
        $wp_taxonomies['post_tag'] = new WP_Taxonomy('post_tag', 'post');

        $noType = 'not-a-type';
        $actual = $this->DataModel->labels('thing', false, [], $noType);

        $this->assertStringContainsString('renaming failed', $actual->msg);
        $this->assertStringContainsString('not a known', $actual->msg);
        $this->assertStringContainsString($noType, $actual->msg);
    }

    public function testUpdateLabels_override()
    {

        $overrideLabel = 'The new page label';

        $actual = $this->DataModel->labels('thing', true, ['override' => $overrideLabel], 'page');
        $this->assertEquals($actual['override'], $overrideLabel);
        $this->assertStringContainsString('Things', $actual['name']);
        $this->assertStringContainsString('Thing', $actual['singular_name']);
    }

    public function testPostTypeLabels()
    {
        $actual = $this->DataModel->postTypeLabels('dog');
        $this->assertStringContainsString('Dogs', $actual['name']);
    }

    public function testPostTypeLabels_old_noInflect()
    {
        $actual = $this->DataModel->postTypeLabels('bird', false);
        $this->assertNotEqualsIgnoringCase('Birds', $actual['name']);
    }

    public function testTaxonomyLabels()
    {
        $actual = $this->DataModel->taxonomyLabels('color');
        $this->assertStringContainsString('Colors', $actual['name']);
    }

    public function testTaxonomyLabels_old_noInflect()
    {
        $actual = $this->DataModel->taxonomyLabels('color', false);
        $this->assertNotEqualsIgnoringCase('Colors', $actual['name']);
    }

    /**
     * Simpler, direct test of updateLabels
     */
    public function testUpdateLabelsPublic(): void
    {
        $labels = new \stdClass();
        $labels->name = 'Frogs';
        $labels->singular_name = 'frog';
        $labels->singularTest = 'This is a frog';
        $labels->pluralTest = 'Look at all those Frogs';
        $actual = $this->DataModel->updateLabels('thing', $labels, true);
        $this->assertStringContainsString('thing', $actual['singularTest']);
        $this->assertStringContainsString('Things', $actual['pluralTest']);
    }

    public function testUpdateLabelsDirect()
    {
        $labels = new \stdClass();
        $labels->name = 'Frogs';
        $labels->singular_name = 'frog';
        $labels->singularTest = 'This is a frog';
        $labels->pluralTest = 'Look at all those Frogs';

        $actual = $this->DataModel->updateLabelsDirect('chicken', 'Chickens', $labels);
        $this->assertStringContainsString('chicken', $actual['singularTest']);
        $this->assertStringContainsString('Chickens', $actual['pluralTest']);
        $this->assertEquals('This is a chicken', $actual['singularTest']);
    }
}
