<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		David Akopyan <davids@efumo.lv>
 * @copyright	Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @version		2
 */

// ------------------------------------------------------------------------

/** 
 * Main validator class
 * This class contain only general validation functions
 * If need more advanced functions please make new class, that extend that one 
 * and include it in constructor.
 * 30.06.2010
 */
class Validator {
	
	protected $rData;
	protected $db;
	protected $cfg;
    protected $action;
	
	/**
	 * Class constructor
	 */
	public function __construct() {
		global $mdb;
		
		$this->db = $mdb;
		$this->cfg = loadLibClass('config');
        $this->action = getP('action');
	}

	public function setFieldsArray($fieldsArray)
	{
		$this->webArray = $fieldsArray;
	}
	
	/**
	 * Check field for correct value
	 * With this function we checking all fields values
	 * 
	 * @param mix		field name
	 * @param mix		field value
	 * @return array	data with fields errors or true if no errors
	 */
	public function checkValue($fieldName, $fieldValue) {

		$fieldValue = clearText($fieldValue);
		
		$this->rData["fieldName"] = $fieldName;
		if (isset($this->webArray[$fieldName])) {
			$this->rData["fields"][$fieldName] = $fieldValue;
			
			if ($this->webArray[$fieldName]["required"] && empty($fieldValue)) {
				
				$this->rData["error"] = true;
				$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_Empty');
				
				return $this->rData;
			}
			if (isset($this->webArray[$fieldName]["errors"]) && count($this->webArray[$fieldName]["errors"])) {
				
				foreach ($this->webArray[$fieldName]["errors"] AS $func) {
						
					$params = array($fieldValue, $fieldName);
					if (isset($this->webArray[$fieldName]["params"][$func])) {
						$params = array_merge($params, $this->webArray[$fieldName]["params"][$func]);
					}
					
					if (!call_user_func_array(array(&$this, $func), $params)) {
						
						return $this->rData;
					} 
				}	
			}
			
		}
				 
		return $this->rData;
	}

	public function returnData()
	{
		return $this->rData;
	}
	
	/**
	 * Check string for personal code
	 * Chec pc checksum
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkPersCode($fieldValue, $fieldName) {
		
		if (getP('fields/resident') == 1) {
			$fieldValue = trim(str_replace("-", "", $fieldValue));
			
			
			if (!$fieldValue || strlen($fieldValue) != 11) {
				$this->rData["error"] = true;
				$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkPersCode');
					
				return false;
			}
			
			
			$checksum = 1;
			for ($i = 0; $i < 10; $i++) {
				$checksum -= (int)substr($fieldValue, $i, 1) * (int)substr("01060307091005080402", $i * 2, 2);
			}
			
			if (($checksum - floor($checksum / 11) * 11) != (int)substr($fieldValue, 10, 1)) {
				$this->rData["error"] = true;
				$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkPersCode');
					
				return false;
			}
			
			return true;
		} else {
			if (getP('fields/person_number') == '') {
				$this->rData["error"] = true;
				$this->rData["errorFields"]['person_number'] = gL('Error_person_number_Empty');
					
				return false;
			}
			
			return true;
		}
		
		
	}
	
	/**
	 * Check string for correct IBAN code
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkIban($fieldValue, $fieldName) {
		$iban = false;
		$fieldValue = strtoupper(trim($fieldValue));

		if(preg_match('/^LV\d{2}[A-Z]{4}\d{13}$/', $fieldValue)) {
			$number = substr($fieldValue, 4) . substr($fieldValue, 0, 4);
			$number = str_replace(
						array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'),
						array(10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35),
						$number
			);

			$iban = (1 == my_bcmod($number, 97)) ? true : false;
		}
		
		if ($iban) {
			return true;
		} else {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkIban');
			
			return false;
		}
	}
	
	/**
	 * Check string for Alpha 
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkAlpha($fieldValue, $fieldName) {
		
		if (!ctype_alpha($fieldValue)) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkAlpha');
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check string for Not Alpha 
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkNotAlpha($fieldValue, $fieldName) {

		if (preg_match('/([[:alpha:]])/iu', $fieldValue)) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkNotAlpha');

			return false;
		}
		
		return true;
	}
	
	/**
	 * Check number for decimal value
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkDecimal($fieldValue, $fieldName) {

		if (!preg_match('/^\+?+\d+((\.|\,)\d{0,2})?$/', $fieldValue)) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkNotAlpha');

			return false;
		}
		
		return true;
	}
	
	/**
	 * Check string for Numbers 
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkNum($fieldValue, $fieldName) {
		
		if (!ctype_digit ($fieldValue)) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkNum');
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check string for AlphaNumeric
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkAlphaNum($fieldValue, $fieldName) {
		
		if (!ctype_alnum($fieldValue)) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkAlphaNum');
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check string for password type
	 * Must be 9 or more symbols and must contain one lowercase, one uppercase, one digit and one special
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkPass($fieldValue, $fieldName) {

        $fieldValue = htmlspecialchars_decode($fieldValue);

		if (!preg_match("/^(?=\S*[a-z])(?=\S*[A-Z])(?=\S*\d)(?=\S*([^\w\s]|[_]))\S{9,}$/", $fieldValue)) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkPass', 'Parolei jābūt vismaz 9 burtu garai, tajā jābūt vismaz vienam mazam un vienam lielajam burtam, ciparam un īpašajai rakstzīmei ("_", "-", "#" un tā tālāk ...)');
			return false;
		}
		return true;
	}

	public function checkPassConfirm($fieldValue, $fieldName) {
        
        $fieldValue = htmlspecialchars_decode($fieldValue);
		
		if ($fieldValue != getP('fields/password')) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkPassConfirm');
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check string lenght 
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @param int		start lenght
	 * @param int		end lenght
	 * @return bool		true or false
	 */
	public function checkLen($fieldValue, $fieldName, $s, $e) {
		if (strlen($fieldValue) < $s || strlen($fieldValue) > $e) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkLen');
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check string for email valid address
	 * 
	 * @param string	field value
	 * @param string	field name
	 * @return bool		true or false
	 */
	public function checkEmail($fieldValue, $fieldName) {
		if (!isValidEmail($fieldValue)) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkEmail');
			
			return false;
		}
		
		return true;
	}

	public function isUniqEmail($value, $field) {

		$dbQuery = "SELECT id
							FROM `" . $this->cfg->getDbTable('profiles', 'self') . "` 
							WHERE 1
							    AND enable = 1 
							    AND deleted = 0  
								AND `email` = '" . mres($value) . "'";

		$query = new query($this->db, $dbQuery);

		if ($query->num_rows() > 0) {

			if ($this->action == 'save' && isset($_SESSION['user'], $_SESSION['user']['email']) && $_SESSION['user']['id'] == $query->getOne()) {
				return true;
			}

			$this->rData["error"] = true;
			$this->rData["errorFields"][$field] = gL('Error_' . $field . '_isUniqEmail');

			return false;

		} else {

			return true;
		}
	}

	public function isUniqPersonId($value, $field) {	
		
		if (getP('fields/resident') != 1) {
			return true;
		}
		
		$dbQuery = "SELECT id
							FROM `" . $this->cfg->getDbTable('profiles', 'self') . "` 
							WHERE 1
								AND `person_id` = '" . mres($value) . "'
								AND enable = 1
								AND `deleted` = 0";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			$id = $query->getOne();
			
			if ($id != getS('user/id')) {
				$this->rData["error"] = true;
				$this->rData["errorFields"][$field] = gL('Error_' . $field . '_isUniqPersonId');
				
				return false;
			}

			return true;
		} else {
			return true;
		}
	}
	
	/**
	 * Check correct hour
	 * from 00 - 23
	 * 
	 * @param string	field value
     * @param string	field name
	 * @return bool		true or false
	 */
	public function checkHour($fieldValue, $fieldName) {
		
		if ($fieldValue > 23 || $fieldValue < 0) {
			
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkHour');
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check correct hour & minutes
	 * 
	 * @param string	field value
     * @param string	field name
	 * @return bool		true or false
	 */
	public function checkNumHour($fieldValue, $fieldName) {
		$time = explode(':', $fieldValue);
		if (isset($time[0], $time[1]) && $this->checkNum($time[0], $fieldName) && $this->checkNum($time[1], $fieldName)) {
			
			if (!$this->checkHour($time[0], $fieldName)) {
				return false;
			}

			if (!$this->checkMinutes($time[1], $fieldName)) {			
				return false;
			} 
			
		} else {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check correct minutes
	 * from 0 - 59
	 * 
	 * @param string	field value
     * @param string	field name
	 * @return bool		true or false
	 */
	public function checkMinutes($fieldValue, $fieldName) {
		
		if ($fieldValue > 59 || $fieldValue < 0) {
			
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkMinutes');
			
			return false;
		}
		
		return true;
	}
	
	/**
     * Check regex from config array value
     *
     * @param string	field value
     * @param string	field name
     * @param regex		regex value
     * @return bool		true or false
     */
    public function checkRegex($fieldValue, $fieldName, $regex) {

		if (!preg_match($regex, $fieldValue)) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkRegex');

			return false;
		}
		
		return true;
    }
    
    /**
     * Check phone number
     *
     * @param string	field value
     * @param string	field name
     * @return bool		true or false
     */
	public function checkPhoneNumber($fieldValue, $fieldName) {

        if (!preg_match("/^(\(?\+?[0-9]*\)?)?[0-9_\- \(\)]*$/", $fieldValue)) {
        	$this->rData["error"] = true;
        	$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkPhoneNumber');
        
        	return false;
        }
        
        if (strlen($fieldValue) < 7) {
        	$this->rData["error"] = true;
        	$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkPhoneNumber');
        
        	return false;
        }

        return true;
    }
    
	/**
     * Function to check & not allow past dates
     *
     * @param string	field value
     * @param string	field name
     * @return bool		true or false
     */
    public function checkNotPastDate($fieldValue, $fieldName) {

		$date = strtotime($fieldValue);
		$today = strtotime(date('d.m.Y', time()));
		if ($date < $today) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkNotPastDate');

			return false;
        }
        
        return true;
    }
	
	/**
	 * Check if one of group elements selected
	 * 
	 * @param string	field value
     * @param string	field name
     * @param array		all group fields values
	 * @return bool		true or false
	 */
	public function checkGroupCkboxRequired($fieldValue, $fieldName, $values){
		
		$this->rData["fields"][$fieldName] = array();
		if (!empty($values)){
			$selected = false;
			foreach ($values as $name => $value) {
				if ($value == 'on') {
					$this->rData["fields"][$name] = true;
					$this->rData["fields"][$fieldName][] = $name;
					$selected = true;
				}
			}
			if (!$selected) {
				$this->rData["error"] = true;
				$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_group_empty');
				return false;
			}
		} else {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_group_empty');
			return false;
		}
		return true;
		
	}
	
	/**
	 * Check positive numeric value
	 * 
	 * @param string	field value
     * @param string	field name
	 * @return bool		true or false
	 */
	public function checkPosNum($fieldValue, $fieldName) {
		if (!$this->checkNum($fieldValue, $fieldName)) {
			return false;
		}
		if ($fieldValue <= 0) {
			$this->rData["error"] = true;
			$this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkPosNum');
			return false;
		}
		
		return true;
	}

    /**
     * @param $fieldValue
     * @param $fieldName
     * @return bool
     */
	public function checkCaptcha($fieldValue, $fieldName) {
        include_once $_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php';
        $securimage = new Securimage();

        if($securimage->check($fieldValue) == false) {
            $this->rData["error"] = true;
            $this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkCaptcha');
            return false;
        }

        return true;
    }

    // is fieldValue valid country string and if this country exists

    /**
     * @param $fieldValue
     * @param $fieldName
     * @return bool
     */
    public function checkCountryString($fieldValue, $fieldName) {

        $isValid = preg_match("/^\D*\(??\)$/", $fieldValue);

        if($isValid) {

            $code = substr($fieldValue, -3, 2);
            $dbQuery = "SELECT id FROM kl_valstis WHERE code2 = '" . mres(strtoupper($code)) . "' LIMIT 1";
            $query = new query($this->db, $dbQuery);

            $isValid = $query->num_rows() > 0;
        }

        if (!$isValid) {
            $this->rData["error"] = true;
            $this->rData["errorFields"][$fieldName] = gL('Error_' . $fieldName . '_checkCountryString', 'You shoud choose valid country');
            return false;
        }
        return true;
    }

}

?>