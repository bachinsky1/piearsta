<?php

// Database tables based on module name
$config['db_tables'] = array(

    // content
    'content'           => 'ad_content',
    'mop'               => 'ad_modules_on_page',
    'modules'           => 'ad_modules',

    // news
    'news'               => 'mod_news',

    //Users
    'users' => array(
        'self' => 'ad_users',
        'roles' => 'ad_user_roles'
    ),
		
	//Banners
	'banners' => 'mod_banners',

    //Clients
    'profiles' => array(
		'self' 		=> 'mod_profiles',
    	'messages' 	=> 'mod_profiles_messages',
    	'coupons' 	=> 'mod_profiles_coupons',
    	'doctors' 	=> 'mod_profiles_doctors',
    	'persons' 	=> 'mod_profiles_persons',
	),

	// Orders
    'orders' => array(
        'self'      => 'mod_orders',
        'info'      => 'mod_order_info',
        'details'   => 'mod_order_details',
        'log'       => 'mod_order_log',
    ),

	// Transactions
    'transactions' => array(
        'self'      => 'mod_transactions',
    ),

	// Logs
    'logs' => array(
        'orders' => 'mod_log_orders',
    ),

	// Coupons
	'coupons' => array(
		'self' 	=> 'mod_coupons',
		'details' 	=> 'mod_coupons_data',
	),

	// Doctors
	'doctors' => array(
		'self' 	=> 'mod_doctors',
		'info' 	=> 'mod_doctors_info',
		'classificators' 	=> 'mod_doctors_to_classificators',
		'clinics' => 'mod_doctors_to_clinics',	
		'holidays' => 'mod_doctors_holidays',
	),

	// Clinics
	'clinics' => array(
		'self' 	=> 'mod_clinics',
		'info' 	=> 'mod_clinics_info',
		'contacts' 	=> 'mod_clinics_contacts',
		'contacts_info' 	=> 'mod_clinics_contacts_info',
		'classificators' 	=> 'mod_clinics_to_classificators',
		'holidays' => 'mod_clinics_holidays',
	),	

	// Reservations
	'reservations' => array(
		'self' 	=> 'mod_reservations',
		'shedules' 	=> 'mod_shedules',
	),

    // Consultations
    'consultations' => array(
        'self' 	=> 'mod_consultations',
    ),

	// Classificators
	'classificators' => array(
		'self' 		=> 'mod_classificators',
		'details' 	=> 'mod_classificators_info',
	),

	// Subscribers
	'subscribers' => array(
        'self'         => 'mod_subscribers',
        'types'        => 'mod_subscribers_type'
    ),

    // Shedule
    'shedule' => array(
    	'self' => 'mod_shedules',
    	'lock' => 'mod_shedules_lock',
    ),

    // Service details (incl. serviceDetails)
    'service' => array(
        'details' => 'mod_service_details',
    ),

    // Api
    'api' => array(
    	'log' => 'mod_api_log',
    )
);

?>