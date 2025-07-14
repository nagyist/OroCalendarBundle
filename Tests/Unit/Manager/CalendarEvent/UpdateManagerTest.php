<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\MatchingEventsManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateAttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateChildManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateExceptionManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateManagerTest extends TestCase
{
    private UpdateAttendeeManager&MockObject $updateAttendeeManager;
    private UpdateChildManager&MockObject $updateChildManager;
    private UpdateExceptionManager&MockObject $updateExceptionManager;
    private MatchingEventsManager&MockObject $matchingEventsManager;
    private FeatureChecker&MockObject $featureChecker;
    private UpdateManager $updateManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->updateAttendeeManager = $this->createMock(UpdateAttendeeManager::class);
        $this->updateChildManager = $this->createMock(UpdateChildManager::class);
        $this->updateExceptionManager = $this->createMock(UpdateExceptionManager::class);
        $this->matchingEventsManager = $this->createMock(MatchingEventsManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->updateManager = new UpdateManager(
            $this->updateAttendeeManager,
            $this->updateChildManager,
            $this->updateExceptionManager,
            $this->matchingEventsManager,
            $this->featureChecker
        );
    }

    public function testOnEventUpdateWithEnabledMasterFeatures(): void
    {
        $entity = new CalendarEvent();
        $entity->setTitle('New Title');

        $originalEntity = clone $entity;
        $originalEntity->setTitle('Original Title test');

        $organization = new Organization();

        $allowUpdateExceptions = true;

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_duplications')
            ->willReturn(true);

        $this->matchingEventsManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($entity);

        $this->updateAttendeeManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($entity, $organization);

        $this->updateChildManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($entity, $originalEntity, $organization);

        $this->updateExceptionManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($entity, $originalEntity);

        $this->updateManager->onEventUpdate($entity, $originalEntity, $organization, $allowUpdateExceptions);
    }

    public function testOnEventUpdateWithDisabledMasterFeatures(): void
    {
        $entity = new CalendarEvent();
        $entity->setTitle('New Title1');

        $originalEntity = clone $entity;
        $originalEntity->setTitle('Original Title test1');

        $organization = new Organization();

        $allowUpdateExceptions = true;

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_duplications')
            ->willReturn(false);

        $this->matchingEventsManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($entity);

        $this->updateAttendeeManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($entity, $organization);

        $this->updateChildManager->expects(self::never())
            ->method('onEventUpdate');

        $this->updateExceptionManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($entity, $originalEntity);

        $this->updateManager->onEventUpdate($entity, $originalEntity, $organization, $allowUpdateExceptions);
    }
}
