<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Attendee extends Constraint
{
    public $message = 'Email or display name have to be specified.';

    #[\Override]
    public function getTargets(): string|array
    {
        return static::CLASS_CONSTRAINT;
    }
}
