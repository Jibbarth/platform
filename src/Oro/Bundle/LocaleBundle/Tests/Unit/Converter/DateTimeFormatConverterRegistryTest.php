<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;
use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterRegistry;

class DateTimeFormatConverterRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DateTimeFormatConverterRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new DateTimeFormatConverterRegistry();
    }

    protected function tearDown()
    {
        unset($this->registry);
    }

    public function testAddFormatConverter()
    {
        $this->assertAttributeEmpty('converters', $this->registry);

        $name = 'test';
        $converter = $this->createFormatConverter();
        $this->registry->addFormatConverter($name, $converter);
        $this->assertAttributeEquals(array($name => $converter), 'converters', $this->registry);
        $this->assertCount(1, $this->registry->getFormatConverters());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Format converter with name "test" already registered
     */
    public function testAddFormatConverterAlreadyRegisteredException()
    {
        $name = 'test';
        $converter = $this->createFormatConverter();
        $this->registry->addFormatConverter($name, $converter);
        $this->registry->addFormatConverter($name, $converter);
    }

    public function testGetFormatConverter()
    {
        $name = 'test';
        $converter = $this->createFormatConverter();
        $this->registry->addFormatConverter($name, $converter);
        $this->assertEquals($converter, $this->registry->getFormatConverter($name));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Format converter with name "test" is not exist
     */
    public function testGetFormatConverterNotExistsException()
    {
        $name = 'test';
        $this->registry->getFormatConverter($name);
    }

    public function getFormatConverters()
    {
        $this->assertEmpty($this->registry->getFormatConverters());

        $name = 'test';
        $converter = $this->createFormatConverter();
        $this->registry->addFormatConverter($name, $converter);
        $this->assertEquals(array($name => $converter), $this->registry->getFormatConverters());
    }

    /**
     * @return DateTimeFormatConverterInterface
     */
    protected function createFormatConverter()
    {
        return $this->getMockBuilder('Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
