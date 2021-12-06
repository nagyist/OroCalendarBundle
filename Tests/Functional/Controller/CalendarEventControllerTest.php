<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Controller;

use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class CalendarEventControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserData::class]);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_calendar_event_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Calendar Events - Activities', $crawler->filter('#page-title')->html());
    }

    public function testCreateAction(): array
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_calendar_event_create'));
        $form = $crawler->selectButton('Save and Close')->form();
        $user = $this->getReference('simple_user');
        $admin = $this->getAdminUser();

        $form['oro_calendar_event_form[title]'] = 'test title extra unique title';
        $form['oro_calendar_event_form[description]'] = 'test description';
        $form['oro_calendar_event_form[start]'] = '2016-05-23T14:46:02Z';
        $form['oro_calendar_event_form[end]'] = '2016-05-23T15:46:02Z';
        $form['oro_calendar_event_form[attendees]'] = implode(
            ContextsToViewTransformer::SEPARATOR,
            [
                json_encode([
                    'entityClass' => get_class($user),
                    'entityId' => $user->getId(),
                ], JSON_THROW_ON_ERROR),
                json_encode([
                    'entityClass' => get_class($admin),
                    'entityId' => $admin->getId(),
                ], JSON_THROW_ON_ERROR)
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Calendar event saved', $crawler->html());

        $attendees = $this->getContainer()->get('doctrine')
            ->getRepository(Attendee::class)
            ->findAll();
        $this->assertCount(2, $attendees);

        $attendeesId = [];
        foreach ($attendees as $attendee) {
            $attendeesId[] = $attendee->getId();
        }

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->findOneBy(['title' => 'test title extra unique title']);

        return [
            'calendarId' => $calendarEvent->getId(),
            'attendees'  => $attendeesId
        ];
    }

    /**
     * @depends testCreateAction
     */
    public function testViewAction(array $param)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_calendar_event_view', ['id' => $param['calendarId']])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCreateAction
     */
    public function testUpdateAction(array $param)
    {
        $response = $this->client->requestGrid(
            'calendar-event-grid',
            ['calendar-event-grid[_filter][title][value]' => 'test title extra unique title']
        );
        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_calendar_event_update', ['id' => $result['id']])
        );
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_calendar_event_form[title]'] = 'test title';
        $form['oro_calendar_event_form[description]'] = 'test description';
        $form['oro_calendar_event_form[start]'] = '2016-05-23T14:46:02Z';
        $form['oro_calendar_event_form[end]'] = '2016-05-23T15:46:02Z';
        $form['oro_calendar_event_form[attendees]'] = implode(
            ContextsToViewTransformer::SEPARATOR,
            [
                json_encode([
                    'entityClass' => Attendee::class,
                    'entityId' => $param['attendees'][0],
                ], JSON_THROW_ON_ERROR),
                json_encode([
                    'entityClass' => Attendee::class,
                    'entityId' => $param['attendees'][1],
                ], JSON_THROW_ON_ERROR)
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Calendar event saved', $crawler->html());

        $attendees = $this->getContainer()->get('doctrine')
            ->getRepository(Attendee::class)
            ->findAll();
        $this->assertCount(2, $attendees);

        foreach ($attendees as $attendee) {
            $this->assertContains($attendee->getId(), $param['attendees']);
        }
    }

    private function getAdminUser(): User
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['email' => self::AUTH_USER]);
    }
}
