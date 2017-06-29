<?php
namespace DoctrineMapper\Parsers\Date;


/**
 * Interface specified overriding date and time format
 *
 * @author Pecina Ondřej <pecina.ondrej@gmail.com>
 * @package DoctrineMapper\Parsers\Date
 */
interface IDateFormat
{

	/**
	 * Return date and time format
	 *
	 * @return string
	 */
	public function getDateTimeFormat() : ?string;

	/**
	 * Return date format
	 *
	 * @return string
	 */
	public function getDateFormat() : ?string;
}