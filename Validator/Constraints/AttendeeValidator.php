<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttendeeValidator extends ConstraintValidator
{
    /**
     * @param AttendeeEntity $value
     * @param Attendee $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if ($value->getCalendarEvent()->getSystemCalendar() || $value->getDisplayName() || $value->getEmail()) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }
}
