<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class DefaultCalendarView extends Page
{
    #[\Override]
    public function open(array $parameters = [])
    {
        $userMenu = $this->elementFactory->createElement('UserMenu');
        $userMenu->find('css', 'i.fa-sort-desc')->click();

        $userMenu->clickLink('My Calendar');
    }
}
