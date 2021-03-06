<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventApiHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventApiHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $notificationManager;

    /** @var CalendarEvent */
    protected $entity;

    /** @var Organization */
    protected $organization;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CalendarEventManager */
    protected $calendarEventManager;

    /** @var CalendarEventApiHandler */
    protected $handler;

    protected function setUp()
    {
        $this->entity  = new CalendarEvent();

        $formData = [
            'contexts' => [],
            'attendees' => new ArrayCollection()
        ];

        $this->request = new Request();
        $this->request->request = new ParameterBag($formData);

        $this->form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo($formData));

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $doctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($objectManager));

        $this->organization = new Organization();
        $securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacade->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->notificationManager = $this->getMockBuilder(NotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->activityManager = $this->getMockBuilder(ActivityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->calendarEventManager = $this
            ->getMockBuilder(CalendarEventManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));

        $objectManager->expects($this->once())
            ->method('flush');

        $this->handler = new CalendarEventApiHandler(
            $this->request,
            $doctrine,
            $securityFacade,
            $this->activityManager,
            $this->calendarEventManager,
            $this->notificationManager
        );

        $this->handler->setForm($this->form);
    }

    public function testProcessWithContexts()
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $owner = new User();
        ReflectionUtil::setId($owner, 321);

        $this->request->setMethod('POST');

        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity->setCalendar($defaultCalendar);

        $defaultCalendar->expects($this->once())
            ->method('getOwner')
            ->will($this->returnValue($owner));


        $this->setExpectedFormValues(['contexts' => [$context]]);

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with(
                $this->entity,
                [$context, $owner]
            );

        $this->activityManager->expects($this->never())
            ->method('removeActivityTarget');

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->handler->process($this->entity);

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    public function testProcessPutWithNotifyInvitedUsersWorks()
    {
        $this->request->setMethod('PUT');

        ReflectionUtil::setId($this->entity, 123);
        $this->entity->addAttendee(new Attendee());

        $this->setExpectedFormValues(['notifyInvitedUsers' => true]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, true);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyInvitedUsersFalseWorks()
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues(['notifyInvitedUsers' => false]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->never())
            ->method($this->anything());

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyInvitedUsersNotPassedWorks()
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues([]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->never())
            ->method($this->anything());

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyInvitedUsersWorks()
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues(['notifyInvitedUsers' => true]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->once())
            ->method('onCreate')
            ->with($this->entity);

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyInvitedUsersFalseWorks()
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues(['notifyInvitedUsers' => false]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->never())
            ->method($this->anything());

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyInvitedUsersNotPassedWorks()
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues([]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->never())
            ->method($this->anything());

        $this->handler->process($this->entity);
    }

    public function testProcessWithClearingExceptions()
    {
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues(['updateExceptions' => true]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->handler->process($this->entity);
    }

    /**
     * @param array $values
     */
    protected function setExpectedFormValues(array $values)
    {
        $fields = ['contexts', 'notifyInvitedUsers', 'updateExceptions'];

        $valueMapHas = [];

        foreach ($fields as $name) {
            $valueMapHas[] = [$name, isset($values[$name])];
        }

        $this->form->expects($this->any())
            ->method('has')
            ->willReturnMap($valueMapHas);

        $valueMapGet = [];

        foreach ($values as $name => $value) {
            $field = $this->createMock('Symfony\Component\Form\FormInterface');
            $field->expects($this->any())
                ->method('getData')
                ->willReturn($value);
            $valueMapGet[] = [$name, $field];
        }

        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap($valueMapGet);
    }
}
