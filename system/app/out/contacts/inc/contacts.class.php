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
 * contacts module
 * 30.06.2010
 */

class contactsData extends Module {

    private $fieldsArray = array(
        'contactsForm' => array(
            'message' => array(
                'required' => true,
            ),
        ),
    );
	
	/**
	 * Class constructor
	 */
	public function __construct() {		
		
		parent :: __construct();
		$this->name = 'contacts';
	}
	
	public function SaveForm() {


//	    pre($_POST);
//	    pre($_GET);
//	    exit;

        /** @var validator $validator */
        $validator = loadLibClass('validator');
        $validator->setFieldsArray($this->fieldsArray['contactsForm']);

        foreach ($this->fieldsArray['contactsForm'] as $field => $data) {
            $validator->checkValue($field, getP($field));
        }

        $result = $validator->returnData();

        if (empty($result['error'])) {

            $_SESSION['contacts']['ok'] = true;

            $name = $_SESSION['user']['name'] . ' ' . $_SESSION['user']['surname'];
            $email = $_SESSION['user']['email'];

            $dbQuery = "SELECT * FROM mod_contact_themes WHERE id=" . mres(getP('theme_select'));
            $query = new query($this->db, $dbQuery);

            $theme = '';

            if($query->num_rows()) {

                $result = $query->getrow();
                $theme = $result['title'];
            }


            $this->sendMailFromClient($email, $name, $theme, getP('message'));
            redirect(getLink($this->getCData('id')));
            return true;

        } else {

            $this->setPData($result, 'contacts');
            return false;
        }
	}

    /**
     * Send email from client
     *
     * @param $email
     * @param $name
     * @param $message
     */
	private function sendMailFromClient($email, $name, $theme, $message) {
		$body = str_replace(
							array('{{email}}', '{{name}}', '{{theme}}', '{{message}}'),
	                    	array(htmlspecialchars($email), htmlspecialchars($name), htmlspecialchars($theme), nl2br(htmlspecialchars($message))),
	                   		$this->cfg->getData('mailBody')
	                   		);

		$mailto = $this->cfg->getData('mailTo');
		$subject = $this->cfg->getData('mailSubj');
		$subject .= ': ' . htmlspecialchars($theme);

		sendMail($mailto, $subject, stripslashes($body), array(), $email);
	}
}

?>