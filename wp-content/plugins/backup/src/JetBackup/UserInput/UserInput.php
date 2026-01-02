<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\UserInput;

use JetBackup\Data\ArrayData;
use JetBackup\Exception\UserInputException;
use JetBackup\Wordpress\Wordpress;

defined("__JETBACKUP__") or die("Restricted Access.");

class UserInput extends ArrayData {

	const INT       = 1<<0; // Signed and Unsigned Integer
	const UINT      = 1<<1; // Unsigned Integer
	const SINT      = 1<<2; // Signed Integer
	const STRING    = 1<<3; // String
	const BOOL      = 1<<4; // Boolean
	const OBJECT    = 1<<5; // Object
	const ARRAY     = 1<<6; // Array
	const FLOAT     = 1<<7; // Signed and Unsigned Float
	const UFLOAT    = 1<<8; // Unsigned Float
	const SFLOAT    = 1<<9; // Signed Float

	const MIXED     = self::INT | self::UINT | self::SINT | self::STRING | self::BOOL | self::OBJECT | self::ARRAY | self::FLOAT | self::UFLOAT | self::SFLOAT;

	const NAMES = [
		self::INT       => 'Integer',
		self::UINT      => 'Unsigned Integer',
		self::SINT      => 'Signed Integer',
		self::STRING    => 'String',
		self::BOOL      => 'Boolean',
		self::OBJECT    => 'Object',
		self::ARRAY     => 'Array',
		self::FLOAT     => 'Float',
		self::UFLOAT    => 'Unsigned Float',
		self::SFLOAT    => 'Signed Float',
		self::MIXED     => 'Mixed',
	];

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateInt($value):bool { return !self::validateArray($value) && !self::validateObject($value) && (trim($value) == (int) $value); }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateUnsignedInt($value):bool { return self::validateInt($value) && ((int) $value) >= 0; }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateSignedInt($value):bool { return self::validateInt($value) && ((int) $value) < 0; }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateString($value):bool { return is_string($value); }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateBool($value):bool { return is_bool($value) || (self::validateInt($value) && (((int) $value) == 0 || ((int) $value) == 1)); }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateObject($value):bool { return is_array($value) || is_object($value); }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateArray($value):bool { return is_array($value); }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateFloat($value):bool { return !self::validateArray($value) && !self::validateObject($value) && (trim($value) == (float) $value); }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateUnsignedFloat($value):bool { return self::validateFloat($value) && ((float) $value) >= 0; }

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function validateSignedFloat($value):bool { return self::validateFloat($value) && ((float) $value) < 0; }

	/**
	 * @param string $key
	 * @param mixed $default
	 * @param int $bits
	 * @param int $subbits
	 *
	 * @return mixed
	 * @throws UserInputException
	 */
	private function _validate(string $key, $default, int $bits, int $subbits) {
		$value = $this->get($key, $default);
		return self::_validateValue($value, $bits, $subbits);
	}

	/**
	 * @param mixed $value
	 * @param int $bits
	 * @param int $subbits
	 *
	 * @return mixed
	 * @throws UserInputException
	 */
	private static function _validateValue($value, int $bits, int $subbits=0) {
		if($bits == self::MIXED) return $value;
		if($bits & self::INT && self::validateInt($value)) return (int) $value;
		if($bits & self::UINT && self::validateUnsignedInt($value)) return (int) $value;
		if($bits & self::SINT && self::validateSignedInt($value)) return (int) $value;
		if($bits & self::STRING && self::validateString($value)) return Wordpress::sanitizeTextField($value);
		if($bits & self::BOOL && self::validateBool($value)) return (bool) $value;
		if($bits & self::OBJECT && self::validateObject($value)) {
			$value = (object) $value;
			if($subbits) foreach($value as $item) self::_validateValue($item, $subbits);
			return $value;
		}
		if($bits & self::ARRAY && self::validateArray($value)) {
			$value = (array) $value;
			if($subbits) foreach($value as $item) self::_validateValue($item, $subbits);
			return $value;
		}
		if($bits & self::FLOAT && self::validateFloat($value)) return (float) $value;
		if($bits & self::UFLOAT && self::validateUnsignedFloat($value)) return (float) $value;
		if($bits & self::SFLOAT && self::validateSignedFloat($value)) return (float) $value;

		throw new UserInputException("Invalid value provided");
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @param int $bits
	 * @param int $subbits
	 *
	 * @return mixed
	 * @throws UserInputException
	 */
	private function _validateArray(string &$key, $default, int $bits, int $subbits) {

		preg_match("/^[^\[]+/", $key, $match);
		preg_match_all("/\[(.*?)]/", $key, $matches);

		$key = $match[0];
		$subkeys = $matches[1];
		$value = $this->get($key, []);
		$invalid = [];

		try {
			foreach($subkeys as $i => $subkey) {

				self::_validateValue($value, UserInput::OBJECT);
				$invalid[] = $subkey;

				$last = $i >= (sizeof($subkeys)-1);
				$value = $value[$subkey] ?? ($last ? $default : []);
				if(!$last) continue;

				return self::_validateValue($value, $bits, $subbits);
			}
		} catch(UserInputException $e) {
			foreach($invalid as $key_name) $key .= "[$key_name]";
			throw $e;
		}

		return $default;
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @param int $bits
	 * @param int $subbits
	 *
	 * @return mixed
	 * @throws UserInputException
	 */
	public function getValidated(string $key, $default, int $bits, int $subbits=0) {

		if(($bits & self::ARRAY || $bits & self::OBJECT) && !$subbits) throw new UserInputException("Invalid field '$key' implementation. You must provide subtype for Array and Object fields");

		try {
			if(preg_match("/\[[^]]+]/", $key)) return $this->_validateArray($key, $default, $bits, $subbits);
			else return $this->_validate($key, $default, $bits, $subbits);
		} catch(UserInputException $e) {
			throw new UserInputException("Invalid data provided for field '%s', Valid data types: %s", [$key, implode(', ', self::_getName($bits, $subbits))]);
		}
	}

	/**
	 * @param int $bits
	 * @param int $subbits
	 *
	 * @return array
	 */
	private static function _getName(int $bits, int $subbits=0):array {
		$name = [];
		if($bits == self::MIXED) return [self::NAMES[self::MIXED]];
		if($bits & self::INT) $name[] = self::NAMES[self::INT];
		if($bits & self::UINT) $name[] = self::NAMES[self::UINT];
		if($bits & self::SINT) $name[] = self::NAMES[self::SINT];
		if($bits & self::STRING) $name[] = self::NAMES[self::STRING];
		if($bits & self::BOOL) $name[] = self::NAMES[self::BOOL];
		if($bits & self::ARRAY || $bits & self::OBJECT) {
			$iname = ($bits & self::ARRAY) ? self::ARRAY : self::OBJECT;
			if($subbits) foreach (self::_getName($subbits) as $sname) $name[] = self::NAMES[$iname] . ' of ' . $sname . 's';
			else $name[] = self::NAMES[$iname];
		}
		if($bits & self::FLOAT) $name[] = self::NAMES[self::FLOAT];
		if($bits & self::UFLOAT) $name[] = self::NAMES[self::UFLOAT];
		if($bits & self::SFLOAT) $name[] = self::NAMES[self::SFLOAT];
		return $name;
	}
}