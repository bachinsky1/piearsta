<?php

/**
 
 * @author		Solvita T�ta <solvita@efs.lv>

 */

// ------------------------------------------------------------------------

/** 
 * Contact class
 * Class validate contactform and send email or return error array. 
 *
 */
class ContactForm {
	/**
	 * Class constructor
	 */
	public function __construct() {
        $this->validator = loadLibClass('validator');
	}
	
	/**
	 * Check field for correct value
	 * With this function we checking all fields values
	 * 
	 * @param array 	post data from contact form
	 * @return array	data with fields errors or true if no errors
	 */
	public function executeContactForm($values, $lang) {
        global $config;
        $data = array();
		foreach($values as $key => $value){
			if(array_key_exists($key, $config['contact_form_fields'])) {
				$roles = explode( '|', $config['contact_form_fields'][$key]['validation_rules']);
				foreach($roles as $role){
                    $result = true;
                    switch ($role){
                        case 'required':
                            if( ($value == '') || gL( 'contacts'.ucfirst( $key ) ) == $value){
                                $result = false;
                            }
                            break;
                        case 'is_email':
                            $result = $this->validator->checkEmail($value, $key);
                            break;
                        case 'phone':
                            $result = $this->validator->checkPhoneNumber($value, $key);
                            break;
                     }
                    if($result == false){
                        $data['error'] = true;
                        $data['errorFields'][$key][$role] = 'error_'.$role;
                    }
				}
            }
		}
        if(isset($data['error']) && $data['error'] == true){
            return $data;
        } else {
            return $this->sendEmailTemplate($values, $lang);
        }
	}
	
	/**
 * Send e-mail template
 * @param   $tmpl_id AS int
 * @param   $values AS array
 * @param   $lang AS string
 */
function sendEmailTemplate($values, $lang) {
    global $mdb, $config;
    
    $query = new query($mdb, "SELECT `id` FROM `mod_email_templates` WHERE `template_key` = '" . $config['contact_form_tmpl'] ."'");
    $tmpl_id = $query->getOne();
    
    if($tmpl_id) {
        $query = new query($mdb, "SELECT * FROM `mod_email_templates_data` WHERE `email_templates_id` = " . intval($tmpl_id) ." AND `lang` = '". $lang ."'");
        $tmpl_val = $query->getrow();
        
        $to = isset($tmpl_val['to_email']) ? clear($tmpl_val['to_email']) : $config['support_email'];
        
        $from = isset($tmpl_val['from_email']) ? clear($tmpl_val['from_email']) : '';
        
        $subject = isset($tmpl_val['email_subject']) ? clear($tmpl_val['email_subject']) : '';
        
        $content = isset($tmpl_val['email_body']) ? stripslashes($tmpl_val['email_body']) : '';
        
        $query = new query($mdb, "SELECT `variable_key` FROM `mod_email_templates_variable` WHERE `email_templates_id` = " . intval($tmpl_id));
        $variables = $query->getarray();
        
        foreach($variables as $val) {
            $val = $val['variable_key'];
            if(array_key_exists($val, $values)) {
                $to = str_replace('{var:' . $val . '}', stripslashes($values[$val]), $to);
                $from = str_replace('{var:' . $val . '}', stripslashes($values[$val]), $from);
                $subject = str_replace('{var:' . $val . '}', stripslashes($values[$val]), $subject);
                $content = str_replace('{var:' . $val . '}', stripslashes($values[$val]), $content);
            }
        }
       
        return sendMail($to, $subject, nl2br($content), array(), $from);
    }
    
    return false;
}

}

?>