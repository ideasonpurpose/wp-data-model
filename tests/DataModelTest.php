<?php

namespace IdeasOnPurpose\WP;

use Exception;
use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use PHPUnit\Framework\MockObject\CannotUseOnlyMethodsException;
use SebastianBergmann\RecursionContext\InvalidArgumentException as RecursionContextInvalidArgumentException;
use PHPUnit\Framework\Exception as FrameworkException;
use PHPUnit\Framework\ExpectationFailedException;
use WP_Taxonomy;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

// class TestDataModel extends \IdeasOnPurpose\WP\DataModel
// {
//     public function register()
//     {
//     }
// }

/**
 * @covers \IdeasOnPurpose\WP\DataModel
 * @covers \IdeasOnPurpose\WP\Plugin\Api
 */
final class DataModelTest extends TestCase
{
    protected function setUp(): void
    {
        unset($GLOBALS['get_terms']);
        unset($GLOBALS['typenow']);
        unset($GLOBALS['taxonomies']);
        unset($GLOBALS['wp_dropdown_categories']);
        unset($GLOBALS['register_taxonomy_for_object_type']);

        //  $this->getMockBuilder( \IdeasOnPurpose\WP\Plugin\API::class)
        // ->disableOriginalConstructor()
        // ->getMock();
    }

    public function testConstructor()
    {
        /**
         * TODO: It would be better if we could properly mock the instantiation of
         * Plugin/Api, but PHPunit won't seem to replace it.
         *
         * This mock /stub appear to go unused
         */
        // $this->createStub(Plugin\Api::class);
        $ApiMock = $this->getMockBuilder(Plugin\Api::class)
            ->disableOriginalConstructor()
            ->getMock();

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $DataModel->__construct();

        /**
         * These checks correspond to individual tests in this file
         **/
        $this->assertContains(['init', 'register'], all_added_actions());
        $this->assertContains(['init', 'parseTaxonomyMap'], all_added_actions());
        $this->assertContains(
            ['restrict_manage_posts', 'parseTaxonomyFilterMap'],
            all_added_actions()
        );

        $this->assertContains(
            ['get_user_option_metaboxhidden_nav-menus', 'navMenuVisibility'],
            all_added_filters()
        );
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

    public function testParseTaxonomyFilterMap_matchTwoPostTypesArray()
    {
        global $typenow, $taxonomies, $wp_dropdown_categories;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();

        $tax = 'Test-Tax-MatchArray';
        $tax2 = 'Test-Tax2-MatchArray';
        $type = 'Test-Type-MatchArray';
        $typenow = $type;

        $wp_dropdown_categories = [];
        $taxonomies[$tax] = new WP_Taxonomy($tax);
        $taxonomies[$tax2] = new WP_Taxonomy($tax2);

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */
        $DataModel->taxonomyFilterMap = [$tax => ['type0', $type], $tax2 => [$type]];
        $DataModel->parseTaxonomyFilterMap();

        $this->assertCount(2, $wp_dropdown_categories);
    }

    public function testNavMenuVisibility()
    {
        global $wp_meta_boxes;

        $DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register', 'getNavMenuNames'])
            ->getMock();

        $DataModel->method('getNavMenuNames')->willReturn(['a', 'b']);

        /** @var \IdeasOnPurpose\WP\DataModel $DataModel */

        $str = 'this string is not false';
        $actual = $DataModel->navMenuVisibility($str);
        $this->assertSame($actual, $str);

        $DataModel->map = ['a', 'c', 'add-category'];
        $wp_meta_boxes = [
            'nav-menus' => [
                'context' => [
                    'priority' => [
                        'aa' => ['id' => 'add-category'],
                        'bb' => ['id' => 'stella'],
                    ],
                ],
            ],
        ];

        $actual = $DataModel->navMenuVisibility(false);
    }
}
