<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarSubscriber;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class CalendarSubscriberTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private ManagerRegistry&MockObject $registry;
    private CalendarSubscriber $calendarSubscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->calendarSubscriber = new CalendarSubscriber($this->tokenAccessor, $this->registry);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA  => 'fillCalendar',
            ],
            $this->calendarSubscriber->getSubscribedEvents()
        );
    }

    public function testFillCalendarIfNewEvent(): void
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $formData = [];
        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn(1);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($formData);

        $repo = $this->createMock(CalendarRepository::class);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(\Oro\Bundle\CalendarBundle\Entity\Calendar::class)
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->willReturn($defaultCalendar);

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertNotNull($event->getData()->getCalendar());
    }

    public function testDoNotFillCalendarIfUpdateEvent(): void
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $eventData->setId(2);
        $eventData->setCalendar($defaultCalendar);
        $formData = [];
        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn(1);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($formData);

        $repo = $this->createMock(CalendarRepository::class);
        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with(\Oro\Bundle\CalendarBundle\Entity\Calendar::class)
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->willReturn($newCalendar);

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertEquals($defaultCalendar, $event->getData()->getCalendar());
    }

    public function testDoNotFillCalendarIfFilledCalendar(): void
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $eventData->setCalendar($defaultCalendar);
        $formData = [];
        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->willReturn(1);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn(1);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($formData);

        $repo = $this->createMock(CalendarRepository::class);
        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with(\Oro\Bundle\CalendarBundle\Entity\Calendar::class)
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->willReturn($newCalendar);

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertEquals($defaultCalendar, $event->getData()->getCalendar());
    }

    public function testDoNotFillCalendarIfSystemCalendar(): void
    {
        $event = $this->createMock(CalendarEvent::class);
        $event->expects($this->once())
            ->method('getSystemCalendar')
            ->willReturn(new SystemCalendar());
        $event->expects($this->never())
            ->method('setCalendar');
        $form = $this->createMock(FormInterface::class);

        $event = new FormEvent($form, $event);
        $this->calendarSubscriber->fillCalendar($event);
    }
}
