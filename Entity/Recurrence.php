<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Recurrence Entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_calendar_recurrence')]
#[ORM\Index(columns: ['start_time'], name: 'oro_calendar_r_start_time_idx')]
#[ORM\Index(columns: ['end_time'], name: 'oro_calendar_r_end_time_idx')]
#[ORM\Index(columns: ['calculated_end_time'], name: 'oro_calendar_r_c_end_time_idx')]
class Recurrence
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'recurrence', targetEntity: CalendarEvent::class)]
    protected ?CalendarEvent $calendarEvent = null;

    /**
     * Determines what recurrence strategy must be used to calculate occurrences of recurring event,
     * to get textual representation etc. Possible values are: daily, weekly, monthly, monthnth, yearly, yearnth.
     *
     * Constants for possible values:
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::TYPE_DAILY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::TYPE_WEEKLY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::TYPE_MONTHLY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::TYPE_MONTH_N_TH
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::TYPE_YEARLY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::TYPE_YEAR_N_TH
     *
     * Corresponding strategies:
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\DailyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\WeeklyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\MonthlyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\MonthNthStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\YearlyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\YearNthStrategy
     */
    #[ORM\Column(name: 'recurrence_type', type: Types::STRING, length: 16)]
    protected ?string $recurrenceType = null;

    /**
     * Contains number of units how often recurring events must repeat.
     * For example, 'every X days', 'every X weeks', 'every X months' (where X is a value of $interval).
     *
     * Units of this attribute depend of recurrenceType.
     * For daily recurrence it is number of days.
     * For weekly recurrence it is number of weeks.
     * For monthly, monthnth recurrences it is number of months.
     * For yearly, yearnth recurrences it is number of month, which is multiple of 12. I.e. 12, 24, 36 etc.
     */
    #[ORM\Column(name: '`interval`', type: Types::INTEGER)]
    protected ?int $interval = null;

    /**
     * Contains a value from 1 to 5, that is relative value for 'first', 'second', 'third', 'fourth' and 'last'.
     *
     * It is used in monthnth and yearnth strategies, for creating recurring events like:
     * 'Yearly every 2 years on the first Saturday of April',
     * 'Monthly the fourth Saturday of every 2 months',
     * 'Yearly every 2 years on the last Saturday of April'.
     *
     * Constants for possible values:
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::INSTANCE_FIRST
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::INSTANCE_SECOND
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::INSTANCE_THIRD
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::INSTANCE_FOURTH
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::INSTANCE_LAST
     */
    #[ORM\Column(name: 'instance', type: Types::INTEGER, nullable: true)]
    protected ?int $instance = null;

    /**
     * Contains array of weekdays.
     *
     * Possible values: 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'.
     * For relative 'weekday' value the array will be ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].
     * For relative 'weekend' value the array will be ['sunday', 'saturday'].
     * For relative 'day' value
     * the array will be ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].
     * In other cases there is no relative value and $dayOfWeek array contains one of possible values.
     *
     * Constants for days:
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::DAY_SUNDAY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::DAY_MONDAY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::DAY_TUESDAY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::DAY_WEDNESDAY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::DAY_THURSDAY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::DAY_FRIDAY
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence::DAY_SATURDAY
     *
     * @var string[]
     */
    #[ORM\Column(name: 'day_of_week', type: Types::ARRAY, nullable: true)]
    protected $dayOfWeek;

    /**
     * Contains day of month that is used by monthly and yearly strategies.
     */
    #[ORM\Column(name: 'day_of_month', type: Types::INTEGER, nullable: true)]
    protected ?int $dayOfMonth = null;

    /**
     * Contains month number that is used by yearly and yearnth strategies.
     */
    #[ORM\Column(name: 'month_of_year', type: Types::INTEGER, nullable: true)]
    protected ?int $monthOfYear = null;

    /**
     * Start datetime for range of recurrence.
     */
    #[ORM\Column(name: 'start_time', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $startTime = null;

    /**
     * End datetime for range of recurrence.
     */
    #[ORM\Column(name: 'end_time', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $endTime = null;

    /**
     * Contains calculated end datetime for range of recurrence.
     * It is used for optimization of SQL query responsible to get all events in some range of time.
     *
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface::getCalculatedEndTime
     */
    #[ORM\Column(name: 'calculated_end_time', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $calculatedEndTime = null;

    /**
     * Contains the number of occurrences for range of recurrence.
     * It means that recurrence ends after X occurrences, where X is value of $occurrences.
     */
    #[ORM\Column(name: 'occurrences', type: Types::INTEGER, nullable: true)]
    protected ?int $occurrences = null;

    /**
     * The time zone in which the time is specified.
     * For recurring events this field is required and specifies the time zone in which the recurrence is expanded.
     *
     * The list of supported Timezones http://php.net/manual/en/timezones.php
     */
    #[ORM\Column(name: 'timezone', type: Types::STRING, length: 255)]
    protected ?string $timeZone = null;

    /**
     * Gets id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param CalendarEvent $calendarEvent
     *
     * @return self
     */
    public function setCalendarEvent(CalendarEvent $calendarEvent)
    {
        $this->calendarEvent = $calendarEvent;

        return $this;
    }

    /**
     * @return CalendarEvent
     */
    public function getCalendarEvent()
    {
        return $this->calendarEvent;
    }

    /**
     * Sets recurrenceType.
     *
     * @param string $recurrenceType
     *
     * @return self
     */
    public function setRecurrenceType($recurrenceType)
    {
        $this->recurrenceType = $recurrenceType;

        return $this;
    }

    /**
     * Gets recurrenceType.
     *
     * @return string
     */
    public function getRecurrenceType()
    {
        return $this->recurrenceType;
    }

    /**
     * Sets interval.
     *
     * @param integer $interval
     *
     * @return self
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Gets interval.
     *
     * @return integer
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Sets instance.
     *
     * @param integer|null $instance
     *
     * @return self
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * Gets instance.
     *
     * @return integer|null
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Sets dayOfWeek.
     *
     * @param array|null $dayOfWeek
     *
     * @return self
     */
    public function setDayOfWeek(?array $dayOfWeek = null)
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    /**
     * Gets dayOfWeek.
     *
     * @return array|null
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * Sets dayOfMonth.
     *
     * @param integer|null $dayOfMonth
     *
     * @return self
     */
    public function setDayOfMonth($dayOfMonth)
    {
        $this->dayOfMonth = $dayOfMonth;

        return $this;
    }

    /**
     * Gets dayOfMonth.
     *
     * @return integer|null
     */
    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * Sets monthOfYear.
     *
     * @param integer|null $monthOfYear
     *
     * @return self
     */
    public function setMonthOfYear($monthOfYear)
    {
        $this->monthOfYear = $monthOfYear;

        return $this;
    }

    /**
     * Gets monthOfYear.
     *
     * @return integer|null
     */
    public function getMonthOfYear()
    {
        return $this->monthOfYear;
    }

    /**
     * Sets startTime.
     *
     * @param \DateTime|null $startTime
     *
     * @return self
     */
    public function setStartTime(?\DateTime $startTime = null)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Gets startTime.
     */
    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    /**
     * Sets endTime.
     *
     * @param \DateTime|null $endTime
     *
     * @return self
     */
    public function setEndTime(?\DateTime $endTime = null)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Gets endTime.
     */
    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    /**
     * @param \DateTime|null $calculatedEndTime
     *
     * @return self
     */
    public function setCalculatedEndTime($calculatedEndTime)
    {
        $this->calculatedEndTime = $calculatedEndTime;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCalculatedEndTime()
    {
        return $this->calculatedEndTime;
    }

    /**
     * Sets occurrences.
     *
     * @param integer|null $occurrences
     *
     * @return self
     */
    public function setOccurrences($occurrences)
    {
        $this->occurrences = $occurrences;

        return $this;
    }

    /**
     * Gets occurrences.
     *
     * @return integer|null
     */
    public function getOccurrences()
    {
        return $this->occurrences;
    }

    /**
     * Sets time zone.
     *
     * @param string $timeZone
     *
     * @return self
     */
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * Gets time zone.
     *
     * @return string
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * Compares instance with another instance and not taking into account relation to event.
     *
     * @param Recurrence|null $other
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isEqual($other)
    {
        if (!$other instanceof Recurrence) {
            return false;
        }

        return
            $this->recurrenceType == $other->recurrenceType &&
            $this->interval == $other->interval &&
            $this->instance == $other->instance &&
            $this->dayOfWeek == $other->dayOfWeek &&
            $this->dayOfMonth == $other->dayOfMonth &&
            $this->monthOfYear == $other->monthOfYear &&
            $this->isDateTimeValueEqual($this->startTime, $other->startTime) &&
            $this->isDateTimeValueEqual($this->endTime, $other->endTime) &&
            $this->occurrences == $other->occurrences &&
            $this->timeZone == $other->timeZone;
    }

    /**
     * @param \DateTime|null $source
     * @param \DateTime|null $target
     * @return bool
     */
    protected function isDateTimeValueEqual(?\DateTime $source = null, ?\DateTime $target = null)
    {
        if ($source && $target) {
            return $source->format('U') == $target->format('U');
        }

        return !$source && !$target;
    }
}
