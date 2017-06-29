<?php
namespace DoctrineMapper\Parsers\Date;

use DateTime;
use DoctrineMapper\Exception\CantParseException;

/**
 * Date time auto parser
 *
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 * @package DoctrineMapper
 */
class DateParser
{
	/** @var IDateFormat */
	private $dateFormat;

	/** @var IDateDecorator */
	private $decorator;

	/**
	 * DateParser constructor.
	 *
	 * @param IDateFormat $dateFormat
	 * @param IDateDecorator $decorator
	 */
	public function __construct(IDateFormat $dateFormat, IDateDecorator $decorator)
	{
		$this->dateFormat = $dateFormat;
		$this->decorator = $decorator;
	}

	/**
	 * Parse string date to DateTime object
	 *
	 * @param string $date
	 * @param bool|FALSE $toDate
	 * @return DateTime
	 *
	 * @throws CantParseException
	 */
	public function parseDateTime(string $date, bool $toDate = FALSE) : ?DateTime
	{
		$value = DateTime::createFromFormat($this->dateFormat->getDateTimeFormat(), $date);

		// if not, try without time
		if (!$value) {
			$value = DateTime::createFromFormat($this->dateFormat->getDateFormat(), $date);

			if ($value !== FALSE) {
				$value->setTime(0,0,0);
			}
		}

		// if is only date version
		if ($toDate) {
			$value->setTime(0,0,0);
		}

		// still cant convert - bad format!!
		if (!$value) {
			throw new CantParseException(sprintf("Bad format date with value '%s'. Expected formats is '%s' or '%s'.", $date, $this->dateFormat->getDateFormat(), $this->dateFormat->getDateTimeFormat()));
		}

		return $this->decorator->decorate($value);
	}

	/**
	 * Parse DateTime to string
	 *
	 * @param DateTime $dateTime
	 * @return string
	 *
	 * @throws CantParseException
	 */
	public function parseString(DateTime $dateTime) : ?string
	{
		if ($dateTime === NULL || get_class($dateTime) !== "DateTime") {
			throw new CantParseException("Bad object or NULL given.");
		}

		if (((int) $dateTime->format("His")) === 0) {
			return $dateTime->format($this->dateFormat->getDateFormat());
		}
		else {
			return $dateTime->format($this->dateFormat->getDateTimeFormat());
		}
	}
}