<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CalendarBundle\Form\Extension\AclObjectLabelTypeExtension;
use Oro\Bundle\SecurityBundle\Form\Type\ObjectLabelType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

class AclObjectLabelTypeExtensionTest extends TestCase
{
    private AclObjectLabelTypeExtension $formExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->formExtension = new AclObjectLabelTypeExtension();
    }

    /**
     * @dataProvider buildViewProvider
     */
    public function testBuildView(string $oldValue, string $newValue): void
    {
        $formView = new FormView();
        $formView->vars['value'] = $oldValue;

        $form = $this->createMock(Form::class);

        $this->formExtension->buildView($formView, $form, []);

        $this->assertEquals($newValue, $formView->vars['value']);
    }

    public function buildViewProvider(): array
    {
        return [
            ['oro.calendar.systemcalendar.entity_label', 'oro.calendar.organization_calendar'],
            ['oro.calendar.calendar.entity_label', 'oro.calendar.calendar.entity_label'],
        ];
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([ObjectLabelType::class], AclObjectLabelTypeExtension::getExtendedTypes());
    }
}
