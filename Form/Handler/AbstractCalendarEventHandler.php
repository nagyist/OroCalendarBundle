<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

abstract class AbstractCalendarEventHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var NotificationManager */
    protected $notificationManager;

    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /**
     * @param RequestStack           $requestStack
     * @param ManagerRegistry        $doctrine
     * @param TokenAccessorInterface $tokenAccessor
     * @param ActivityManager        $activityManager
     * @param CalendarEventManager   $calendarEventManager
     * @param NotificationManager    $notificationManager
     */
    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        TokenAccessorInterface $tokenAccessor,
        ActivityManager $activityManager,
        CalendarEventManager $calendarEventManager,
        NotificationManager $notificationManager
    ) {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
        $this->activityManager = $activityManager;
        $this->calendarEventManager = $calendarEventManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * Get form, that build into handler, via handler service
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * "Success" form handler
     *
     * @param CalendarEvent $entity
     * @param CalendarEvent $originalEntity
     */
    protected function onSuccess(CalendarEvent $entity, CalendarEvent $originalEntity)
    {
        $this->calendarEventManager->onEventUpdate(
            $entity,
            $originalEntity,
            $this->tokenAccessor->getOrganization(),
            $this->allowUpdateExceptions()
        );

        $isNew = $entity->getId() ? false : true;
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        $this->sendNotifications($entity, $originalEntity, $isNew);
    }

    /**
     * Returns TRUE if exceptions of recurring event are allowed to clear and update if necessary.
     *
     * @return bool
     */
    abstract protected function allowUpdateExceptions();

    /**
     * Sends notification for calendar event if this is required.
     *
     * @param CalendarEvent $entity
     * @param CalendarEvent $originalEntity
     * @param boolean $isNew
     */
    protected function sendNotifications(CalendarEvent $entity, CalendarEvent $originalEntity, $isNew)
    {
        if ($isNew) {
            $this->notificationManager->onCreate($entity, $this->getSendNotificationsStrategy());
        } else {
            $this->notificationManager->onUpdate($entity, $originalEntity, $this->getSendNotificationsStrategy());
        }
    }

    /**
     * @see NotificationManager::ALL_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::NONE_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
     *
     * @return string
     */
    abstract protected function getSendNotificationsStrategy();

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('Request was not found');
        }

        return $request;
    }
}
