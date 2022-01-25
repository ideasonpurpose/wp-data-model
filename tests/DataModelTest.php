<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
// use DataModel;

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
        // $this->mockAPI = $this->getMockBuilder(Plugin\API::class)
        //     ->disableOriginalConstructor()
        //     ->getMock();

        /** @var \IdeasOnPurpose\WP\DataModel $this->Taxonomy */
        $this->DataModel = $this->getMockBuilder(DataModel::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        // $this->DataModel = $this->getMockForAbstractClass(DataModel::class);
        // $this->DataModel->slug = 'test';
        // $this->DataModel->args = [];

        /**
         * Work with the protected taxonomyMap variable
         */
        // $Ref = new \ReflectionClass($this->DataModel);
        // $prop = $Ref->getProperty('taxonomyMap');
        // $prop->setAccessible(true);
        // $prop->setValue($this->DataModel, 'Test CSS String');
    }

    public function testParseTaxonomyMap()
    {
        global $register_taxonomy_for_object_type;
        $taxonomyMap = ['test' => ['type0', 'type1']];

        $Ref = new \ReflectionClass($this->DataModel);
        $prop = $Ref->getProperty('taxonomyMap');
        $prop->setAccessible(true);
        $prop->setValue($this->DataModel, $taxonomyMap);

        $this->DataModel->parseTaxonomyMap();

        $this->assertCount('2', $register_taxonomy_for_object_type);
        $this->assertContains('type0', $register_taxonomy_for_object_type[0]);
        $this->assertContains('type1', $register_taxonomy_for_object_type[1]);
    }
}
