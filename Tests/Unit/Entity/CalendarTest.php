<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class CalendarTest extends TestCase
{
    public function testIdGetter(): void
    {
        $obj = new Calendar();
        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value): void
    {
        $obj = new Calendar();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function testEvents(): void
    {
        $obj = new Calendar();
        $event = new CalendarEvent();
        $obj->addEvent($event);
        $this->assertCount(1, $obj->getEvents());
        $events = $obj->getEvents();
        $this->assertSame($event, $events[0]);
        $this->assertSame($obj, $events[0]->getCalendar());
    }

    public function testToString(): void
    {
        $obj = new Calendar();
        $obj->setName('testName');
        $this->assertEquals($obj->getName(), (string)$obj);
    }

    public function testToStringDefault(): void
    {
        $obj = new Calendar();
        $this->assertEquals('[default]', (string)$obj);
    }

    public function testToStringUsername(): void
    {
        $obj = new Calendar();
        $owner = new User();
        $owner->setUsername('testUsername');
        $obj->setOwner($owner);

        $this->assertEquals($owner->getUserIdentifier(), (string)$obj);
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['name', 'testName'],
            ['owner', new User()],
            ['organization', new Organization()],
        ];
    }
}
