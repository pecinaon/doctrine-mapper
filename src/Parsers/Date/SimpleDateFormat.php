<?php
namespace DoctrineMapper\Parsers\Date;

/**
 * Interface specified overriding date and time format
 *
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 * @package DoctrineMapper\Parsers\Date
 */
class SimpleDateFormat implements IDateFormat
{
	const DATE_TIME_FORMAT = 'd.m.Y H:i:s';
	const DATE_FORMAT = 'd.m.Y';

	/**
	 * @return string
	 */
	public function getDateTimeFormat() : string
	{
		return self::DATE_TIME_FORMAT;
	}

	/**
	 * @return string
	 */
	public function getDateFormat() : string
	{
		return self::DATE_FORMAT;
	}
}