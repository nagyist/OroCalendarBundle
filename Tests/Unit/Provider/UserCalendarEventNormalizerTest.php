<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class UserCalendarEventNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attendeeManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $reminderManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var UserCalendarEventNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->calendarEventManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attendeeManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->reminderManager = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new UserCalendarEventNormalizer(
            $this->calendarEventManager,
            $this->attendeeManager,
            $this->reminderManager,
            $this->securityFacade
        );
    }

    /**
     * @dataProvider getCalendarEventsProvider
     * @param array $events
     * @param array $attendees
     * @param array $editableInvitationStatus
     * @param array $expected
     */
    public function testGetCalendarEvents(array $events, array $attendees, $editableInvitationStatus, array $expected)
    {
        $calendarId = 123;

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($events));

        if (!empty($events)) {
            $this->securityFacade->expects($this->exactly(2))
                ->method('isGranted')
                ->will(
                    $this->returnValueMap(
                        [
                            ['oro_calendar_event_update', null, true],
                            ['oro_calendar_event_delete', null, true],
                        ]
                    )
                );
        }

        if ($events) {
            $loggedUser = new User();

            $this->securityFacade->expects($this->once())
                ->method('getLoggedUser')
                ->willReturn($loggedUser);

            $this->calendarEventManager->expects($this->once())
                ->method('canChangeInvitationStatus')
                ->with($this->isType('array'), $loggedUser)
                ->willReturn($editableInvitationStatus);

            $this->attendeeManager->expects($this->once())
                ->method('getAttendeeListsByCalendarEventIds')
                ->will($this->returnCallback(function ($calendarEventIds) use ($attendees) {
                    return array_intersect_key($attendees, array_flip($calendarEventIds));
                }));
        } else {
            $this->securityFacade->expects($this->never())->method($this->anything());
            $this->calendarEventManager->expects($this->never())->method($this->anything());
            $this->attendeeManager->expects($this->never())->method($this->anything());
        }

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getCalendarEventsProvider()
    {
        $startDate = new \DateTime();
        $endDate = $startDate->add(new \DateInterval('PT1H'));

        return [
            'no events' => [
                'events' => [],
                'invitees' => [],
                'editableInvitationStatus' => null,
                'expected' => []
            ],
            'event without invitees' => [
                'events' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate,
                        'end' => $endDate,
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'relatedAttendeeUserId' => 1,
                    ],
                ],
                'attendees' => [1 => []],
                'editableInvitationStatus' => false,
                'expected' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate->format('c'),
                        'end' => $endDate->format('c'),
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'attendees' => [],
                        'editable' => true,
                        'editableInvitationStatus' => false,
                        'removable' => true,
                        'notifiable' => false,
                    ],
                ]
            ],
            'event with invitees' => [
                'events' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate,
                        'end' => $endDate,
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'relatedAttendeeUserId' => 1,
                    ],
                ],
                'attendees' => [
                    1 => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'userId' => null
                        ],
                    ],
                ],
                'editableInvitationStatus' => false,
                'expected' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate->format('c'),
                        'end' => $endDate->format('c'),
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'editable' => true,
                        'editableInvitationStatus' => false,
                        'removable' => true,
                        'notifiable' => true,
                        'attendees' => [
                            [
                                'displayName' => 'user',
                                'email' => 'user@example.com',
                                'userId' => null
                            ],
                        ],
                    ],
                ]
            ],
            'event with invitees and editable invitation status' => [
                'events' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate,
                        'end' => $endDate,
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'relatedAttendeeUserId' => 1,
                    ],
                ],
                'attendees' => [
                    1 => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'userId' => 1
                        ],
                    ],
                ],
                'editableInvitationStatus' => true,
                'expected' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate->format('c'),
                        'end' => $endDate->format('c'),
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'editable' => true,
                        'editableInvitationStatus' => true,
                        'removable' => true,
                        'notifiable' => true,
                        'attendees' => [
                            [
                                'displayName' => 'user',
                                'email' => 'user@example.com',
                                'userId' => 1
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider getCalendarEventProvider
     * @param array $event
     * @param int $calendarId
     * @param boolean $editableInvitationStatus
     * @param array $expected
     */
    public function testGetCalendarEvent(array $event, $calendarId, $editableInvitationStatus, array $expected)
    {
        $loggedUser = new User();

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($loggedUser);

        $this->calendarEventManager->expects($this->once())
            ->method('canChangeInvitationStatus')
            ->with($this->isType('array'), $loggedUser)
            ->willReturn($editableInvitationStatus);

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_calendar_event_update', null, true],
                        ['oro_calendar_event_delete', null, true],
                    ]
                )
            );

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with([$expected], 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $this->attendeeManager->expects($this->never())
            ->method('getAttendeeListsByCalendarEventIds');

        $result = $this->normalizer->getCalendarEvent(
            $this->buildCalendarEvent($event),
            $calendarId
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getCalendarEventProvider()
    {
        $startDate = new \DateTime();
        $endDate = $startDate->add(new \DateInterval('PT1H'));

        return [
            'calendar not specified' => [
                'event' => [
                    'calendar' => 123,
                    'id' => 1,
                    'title' => 'test',
                    'description' => null,
                    'start' => $startDate,
                    'end' => $endDate,
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'attendees' => [],
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                ],
                'calendarId' => null,
                'editableInvitationStatus' => false,
                'expected' => [
                    'calendar' => 123,
                    'id' => 1,
                    'title' => 'test',
                    'description' => null,
                    'start' => $startDate->format('c'),
                    'end' => $endDate->format('c'),
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'editable' => true,
                    'editableInvitationStatus' => false,
                    'removable' => true,
                    'notifiable' => false,
                    'attendees' => [],
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                ]
            ],
            'own calendar' => [
                'event' => [
                    'calendar' => 123,
                    'id' => 1,
                    'title' => 'test',
                    'description' => null,
                    'start' => $startDate,
                    'end' => $endDate,
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'attendees' => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'status' => Attendee::STATUS_NONE,
                        ]
                    ],
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                ],
                'calendarId' => 123,
                'editableInvitationStatus' => false,
                'expected' => [
                    'calendar' => 123,
                    'id' => 1,
                    'title' => 'test',
                    'description' => null,
                    'start' => $startDate->format('c'),
                    'end' => $endDate->format('c'),
                    'allDay' => null,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'editable' => true,
                    'editableInvitationStatus' => false,
                    'removable' => true,
                    'notifiable' => true,
                    'attendees' => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'userId' => null,
                            'createdAt' => null,
                            'updatedAt' => null,
                            'status' => Attendee::STATUS_NONE,
                            'type' => null,
                        ]
                    ],
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                ]
            ],
            'another calendar' => [
                'event' => [
                    'calendar' => 123,
                    'id' => 1,
                    'title' => 'test',
                    'start' => $startDate,
                    'end' => $endDate,
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                ],
                'calendarId' => 456,
                'editableInvitationStatus' => false,
                'expected' => [
                    'calendar' => 123,
                    'id' => 1,
                    'title' => 'test',
                    'description' => null,
                    'start' => $startDate->format('c'),
                    'end' => $endDate->format('c'),
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'attendees' => [],
                    'editable' => false,
                    'editableInvitationStatus' => false,
                    'removable' => false,
                    'notifiable' => false,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param array $data
     *
     * @return CalendarEvent
     */
    protected function buildCalendarEvent(array $data)
    {
        $event = new CalendarEvent();

        if (!empty($data['id'])) {
            $reflection = new \ReflectionProperty(get_class($event), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($event, $data['id']);
        }
        if (!empty($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (!empty($data['start'])) {
            $event->setStart($data['start']);
        }
        if (!empty($data['end'])) {
            $event->setEnd($data['end']);
        }
        if (isset($data['allDay'])) {
            $event->setAllDay($data['allDay']);
        }
        if (!empty($data['calendar'])) {
            $calendar = new Calendar();
            $calendar->setOwner(new User(1));
            $event->setCalendar($calendar);
            $reflection = new \ReflectionProperty(get_class($calendar), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($calendar, $data['calendar']);
        }

        if (!empty($data['attendees'])) {
            foreach ($data['attendees'] as $attendeeData) {
                $attendee = new Attendee();
                $attendee->setEmail($attendeeData['email']);
                $attendee->setDisplayName($attendeeData['displayName']);

                if (array_key_exists('status', $attendeeData)) {
                    $status = new TestEnumValue($attendeeData['status'], $attendeeData['status']);
                    $attendee->setStatus($status);
                }

                $event->addAttendee($attendee);
            }
        }

        return $event;
    }
}
