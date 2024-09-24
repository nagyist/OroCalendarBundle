<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Provides a way to use CalendarEvent entity in an activity list.
 */
class CalendarEventActivityListProvider implements
    ActivityListProviderInterface,
    CommentProviderInterface,
    ActivityListDateProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityAssociationHelper */
    protected $activityAssociationHelper;

    /** @var CommentAssociationHelper */
    protected $commentAssociationHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ActivityAssociationHelper $activityAssociationHelper,
        CommentAssociationHelper $commentAssociationHelper
    ) {
        $this->doctrineHelper            = $doctrineHelper;
        $this->activityAssociationHelper = $activityAssociationHelper;
        $this->commentAssociationHelper  = $commentAssociationHelper;
    }

    #[\Override]
    public function isApplicableTarget($entityClass, $accessible = true)
    {
        return $this->activityAssociationHelper->isActivityAssociationEnabled(
            $entityClass,
            CalendarEvent::class,
            $accessible
        );
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getRoutes($entity)
    {
        $routes = [
            'itemViewLink' => 'oro_calendar_event_view',
            'itemView' => 'oro_calendar_event_widget_info',
            'itemEdit' => 'oro_calendar_event_update',
            'itemDelete' => 'oro_calendar_event_delete'
        ];

        if ($entity->getSystemCalendar() instanceof SystemCalendar) {
            $routes = array_merge(
                $routes,
                [
                    'itemViewLink' => 'oro_system_calendar_event_view',
                    'itemView' => 'oro_system_calendar_event_widget_info',
                    'itemEdit' => 'oro_system_calendar_event_update',
                ]
            );
        }

        return $routes;
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getSubject($entity)
    {
        return $entity->getTitle();
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getDescription($entity)
    {
        return trim(strip_tags($entity->getDescription()));
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getCreatedAt($entity)
    {
        return $entity->getCreatedAt();
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getUpdatedAt($entity)
    {
        return $entity->getUpdatedAt();
    }

    #[\Override]
    public function getData(ActivityList $activityList)
    {
        return [];
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getOrganization($entity)
    {
        if ($entity->getCalendar()) {
            return $entity->getCalendar()->getOrganization();
        }
        if ($entity->getSystemCalendar()) {
            return $entity->getSystemCalendar()->getOrganization();
        }

        return null;
    }

    #[\Override]
    public function getTemplate()
    {
        return '@OroCalendar/CalendarEvent/js/activityItemTemplate.html.twig';
    }

    #[\Override]
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    #[\Override]
    public function isApplicable($entity)
    {
        if (\is_object($entity)) {
            return $entity instanceof CalendarEvent && !$entity->getRecurringEvent();
        }

        return $entity === CalendarEvent::class;
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargets();
    }

    #[\Override]
    public function isCommentsEnabled($entityClass)
    {
        return $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass);
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getActivityOwners($entity, ActivityList $activityList)
    {
        $organization = $this->getOrganization($entity);
        $owner = $this->getOwner($entity);

        if (!$organization || !$owner) {
            return [];
        }

        $activityOwner = new ActivityOwner();
        $activityOwner->setActivity($activityList);
        $activityOwner->setOrganization($organization);
        $activityOwner->setUser($owner);

        return [$activityOwner];
    }

    #[\Override]
    public function isActivityListApplicable(ActivityList $activityList): bool
    {
        return true;
    }

    /**
     * @param CalendarEvent $entity
     */
    #[\Override]
    public function getOwner($entity)
    {
        if ($entity->getCalendar()) {
            return $entity->getCalendar()->getOwner();
        }

        return null;
    }
}
