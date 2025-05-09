<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with attendees:
 *   - is_attendees_invitation_enabled
 */
class AttendeesExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return ['oro_featuretoggle.checker.feature_checker'];
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('is_attendees_invitation_enabled', [$this, 'isAttendeesInvitationEnabled'])
        ];
    }

    public function isAttendeesInvitationEnabled(): bool
    {
        return $this->container->get('oro_featuretoggle.checker.feature_checker')
            ->isFeatureEnabled('calendar_events_attendee_notifications');
    }
}
