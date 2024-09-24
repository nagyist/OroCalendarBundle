<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Exception\ChangeInvitationStatusException;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * AJAX calendar event controller
 */
#[Route(path: '/event/ajax')]
class AjaxCalendarEventController extends AbstractController
{
    /**
     *
     * @param CalendarEvent $entity
     * @param string        $status
     *
     * @return JsonResponse
     */
    #[Route(
        path: '/accepted/{id}',
        name: 'oro_calendar_event_accepted',
        requirements: ['id' => '\d+'],
        defaults: ['status' => 'accepted'],
        methods: ['POST']
    )]
    #[Route(
        path: '/tentative/{id}',
        name: 'oro_calendar_event_tentative',
        requirements: ['id' => '\d+'],
        defaults: ['status' => 'tentative'],
        methods: ['POST']
    )]
    #[Route(
        path: '/declined/{id}',
        name: 'oro_calendar_event_declined',
        requirements: ['id' => '\d+'],
        defaults: ['status' => 'declined'],
        methods: ['POST']
    )]
    #[CsrfProtection()]
    public function changeStatusAction(CalendarEvent $entity, $status)
    {
        try {
            $loggedUser = $this->container->get(TokenAccessorInterface::class)->getUser();
            $manager = $this->container->get(CalendarEventManager::class);
            $manager->changeInvitationStatus($entity, $status, $loggedUser);
        } catch (ChangeInvitationStatusException $exception) {
            return new JsonResponse(
                [
                    'successfull' => false,
                    'message'     => $exception->getMessage(),
                ]
            );
        }

        $this->container->get('doctrine')
            ->getManagerForClass(CalendarEvent::class)
            ->flush();

        $this->container->get(NotificationManager::class)->onChangeInvitationStatus(
            $entity,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );

        return new JsonResponse(['successful' => true]);
    }

    /**
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route(
        path: '/attendees-autocomplete-data/{id}',
        name: 'oro_calendar_event_attendees_autocomplete_data',
        options: ['expose' => true]
    )]
    public function attendeesAutocompleteDataAction($id)
    {
        $attendeeManager = $this->container->get(AttendeeManager::class);
        $attendees = $attendeeManager->loadAttendeesByCalendarEventId($id);

        $attendeeRelationManager = $this->container->get(AttendeeRelationManager::class);

        $result = [];

        foreach ($attendees as $attendee) {
            $result[] = [
                'text'        => $attendeeRelationManager->getDisplayName($attendee),
                'displayName' => $attendee->getDisplayName(),
                'email'       => $attendee->getEmail(),
                'type'        => $attendee->getType() ? $attendee->getType()->getId() : null,
                'status'      => $attendee->getStatus() ? $attendee->getStatus()->getId() : null,
                'userId'      => $attendee->getUser() ? $attendee->getUser()->getId() : null,
                /**
                 * Selected Value Id should additionally encoded because it should be used as string key
                 * to compare with value
                 */
                'id'          => json_encode(
                    [
                        'entityClass' => Attendee::class,
                        'entityId'    => $attendee->getId(),
                    ]
                )
            ];
        }

        return new JsonResponse([
            'result'   => $result,
            'excluded' => $attendeeManager->createAttendeeExclusions($attendees),
        ]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TokenAccessorInterface::class,
                CalendarEventManager::class,
                NotificationManager::class,
                AttendeeRelationManager::class,
                AttendeeManager::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
