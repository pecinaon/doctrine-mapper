<?php
namespace DoctrineMapper\Parsers\Date;

use DateTime;

/**
 * Decorate date time
 *
 * Ondřej Pecina <pecina.ondrej@gmail.com>
 * @package DoctrineMapper\Parsers\Date
 */
class SimpleDateDecorator implements IDateDecorator
{

	/**
	 * Decorate date time and return
	 *
	 * @param DateTime $dateTime
	 * @return DateTime
	 */
	public function decorate(DateTime $dateTime)
	{
		return $dateTime;
	}
}