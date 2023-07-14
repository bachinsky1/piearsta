var sendData;
var profile = {
	
	langs : {},
	doctorId : '',
	clinicId : '',
	deletePersonId : false,
	messagesToDelete : [],
	reservationsFilter : {},

	temporaryLockedSlots: null,

	filterCalendar: false,

	sessionTimeouts: {
		sessLength: null,
		sessWarnBefore: null,
	},

	sessionPopupHtml: null,

	sessionTimer: null,
	sessionWarnTimeout: null,
	sessionWarnInterval: null,

	reservationProgress: {
		inProgress: false,
		isPaid: false,
		isCountryPaid: false,
		price: null,
		serviceId: null,
		serviceName: '',
		serviceDescriptionLink: '',
		serviceDescription: null,
	},

	noticeVoiceInput: null,

	verificationMethods: {
		'1': 'smartid',
		'2': 'eid'
	},

    selectedServiceId: null,
	selectedServicePrice: null,
	selectedServiceOrigPrice: null,

	scrollPosition: null,
    waitTimer: null,
	SMcheckResult: null,
	lockStatuses: {
		lockLocally: 1,
		pending: 2,
		autoconfirmed: 3,
		confirmed: 4,
		nonConfirmed: 5
	},

	setSessionTimer: function() {
		profile.sessionTimer = setTimeout(function () {
			profile.sessionTimeoutPopup();
		}, profile.sessionTimeouts.sessLength - profile.sessionTimeouts.sessWarnBefore)
	},

	clearSessionTimers: function() {
		clearInterval(profile.sessionWarnInterval);
		clearTimeout(profile.sessionWarnTimeout);
		clearTimeout(profile.sessionTimer);
	},

	sessionTimeoutPopup: function() {

		var countDown = profile.sessionTimeouts.sessWarnBefore / 1000;

		var setTimeLeft = function() {

			var minLeft = Math.floor(countDown / 60),
				secLeft = countDown % 60;

			minLeft = ('' + minLeft).length > 1 ? minLeft : '0' + minLeft;
			secLeft = ('' + secLeft).length > 1 ? secLeft : '0' + secLeft;

			$('.session_timeout_popup .countDown').text(minLeft + ':' + secLeft);
		};

		$('body').append(Base64.decode(profile.sessionPopupHtml));
		position_popup();

		setTimeLeft();

		profile.sessionWarnInterval = setInterval(function () {
			setTimeLeft();
			countDown--;
		}, 1000);

		profile.sessionWarnTimeout = setTimeout(function () {
			profile.clearSessionTimers();
			profile.sessionTimeoutConfirm(false);
		}, profile.sessionTimeouts.sessWarnBefore);
	},

	sessionTimeoutConfirm: function(response) {

		if(response) {

			// ajax to refresh session and continue
			// reset timers

			$('.popup_bg_session, .session_timeout_popup').remove();

			profile.clearSessionTimers();
			profile.sessionPing();
			profile.setSessionTimer();

		} else {

			// logout

			profile.clearSessionTimers();

			$('.popup_bg_session, .session_timeout_popup').remove();

			profile.logout();
		}
	},

	sessionPing: function() {

		ajaxRequest('/profile/sessionPing/', {}, function(data) {

			if(data.logged_off) {
				window.location.href = data.location ? data.location : '/';
			}

			if(data.logged) {
				profile.clearSessionTimers();
				profile.setSessionTimer();
			} else {
				profile.clearSessionTimers();
			}
		});
	},

	logout: function() {

		ajaxRequest('/profile/logout/', {}, function(data) {

			if(data.location) {
				location.href = data.location;
			}
		});
	},
	
	subscribe : function () {

		profile.sessionPing();

		var $subscribeEmail = $('#subscribe_email'),
			$newsletterBlock = $('.newsletter_block');
		
		if ($subscribeEmail.val()) {
			sendData = {};
			sendData['subscribe_email'] = $subscribeEmail.val();
			
			ajaxRequest('/profile/subscribe/', sendData, function(data) {
	            
				if (data.ok) {
					$newsletterBlock.find('.error_msg').hide();
					$newsletterBlock.find('.success_msg').show();

					$newsletterBlock.find('.cinput').hide();
					$newsletterBlock.find('.note').hide();
					$newsletterBlock.find('#newsleter_submit').hide();

					$newsletterBlock.find('#newsleter_submit_ok').show();
					
				} else if (data.error) {
					$newsletterBlock.find('.success_msg').hide();
					$newsletterBlock.find('.error_msg').show();
				}  
				
			});
		}
		
	},
	
	showSubscribe : function () {

		var $newsletterBlock = $('.newsletter_block');


		$newsletterBlock.find('.error_msg').hide();
		$newsletterBlock.find('.success_msg').hide();

		$newsletterBlock.find('.cinput').show().find('input').val('');
		$newsletterBlock.find('.note').show();
		$newsletterBlock.find('#newsleter_submit').show();

		$newsletterBlock.find('#newsleter_submit_ok').hide();
	},
	
	pdfCoupon : function (couponId) {

		profile.sessionPing();

		sendData = {};
		sendData['couponId'] = couponId;
		
		$.ajax({
			type: 'POST',
		    url: '/profile/pdfCoupon/',
		    dataType: 'json',
		    data : sendData,
		    async: false,
		    success: function(data) {

				if(data.logged_off) {
					location.reload();
				}

		    	if (data.location) {
					 var win = window.open(data.location, '_blank');
					 win.focus();
				} 
		    }
		});
	},
	
	openReservation : function (reservationId) {

		profile.sessionPing();

		$('.popup_bg,.popup').remove();
		$('html').removeClass('popup_open');
		
		sendData = {};
		sendData['reservationId'] = reservationId;
		
		ajaxRequest('/profile/openReservation/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			$('body').append(data.html);
			position_popup();
			tip();

			$('.popup .darbibas').off('click').on('click', function (e) {

				profile.initConsultationActionLink(this);
			});
			
			$('.popup .close').on('click', function() {
				profile.closePopup();
			});
			
		});
	},

	openConsultation : function (consultationId) {

		profile.sessionPing();

		$('.popup_bg,.popup').remove();
		$('html').removeClass('popup_open');

		sendData = {};
		sendData['consultationId'] = consultationId;

		ajaxRequest('/profile/openConsultation/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			$('body').append(data.html);
			position_popup();
			tip();

			$('.popup .close').on('click', function() {
				profile.closePopup();
			});

		});
	},
	
	cancelReservationPopup : function (reservationId) {

		profile.sessionPing();

		this.closePopup();
		
		sendData = {};
		sendData['reservationId'] = reservationId;
		
		ajaxRequest('/profile/cancelReservationPopup/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			$('body').append(data.html);
			position_popup();
			tip();

			// Using select plugin Selectric
			$('#reason').selectric({
				arrowButtonMarkup: '<b class="button">&#x25b2;</b>'
			});

			$('.popup .close').click(function() {
				profile.closePopup();
			}); 
			
		});
	},
	
	cancelReservation : function (reservationId) {

		profile.sessionPing();
		profile.removeErrors();

		var $refund = $('#refund'),
			$refundAccount = $('#refund_account');
		
		sendData = {};
		sendData['reservationId'] = reservationId;
		sendData['reason'] = $('#reason').val();
		sendData['status_reason'] = $('#status_reason').val();

		if($refund.length) {
			sendData['refund'] = $refund.is(':checked') ? '1' : '0';
		}

		if($refundAccount.length && $refundAccount.is(':visible')) {
			sendData['refund_account'] = $refundAccount.val();
		}

		var $refundAccountErrorCase = $('#refund_account_error_case');
		if($refundAccountErrorCase.length && $refundAccountErrorCase.is(':visible')) {
			sendData['refund_account_error_case'] = $refundAccountErrorCase.val();
		}

		ajaxRequest('/profile/cancelReservation/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
			
			if (data.errors) {
				if(data.errors.fields.refund_account_error_case){
					$('.refund_account_error_case').css({display: 'block'});
				}
				profile.setErrors(data.errors);
			} else {
				
				$('.popup_bg,.popup').remove();
				$('html').removeClass('popup_open');
				
				$('body').append(data.html);
				position_popup();
				tip();
				
				$('.popup .close, .popup_bg').off('click').on('click', function() {
					profile.closePopup();
					location.reload();
				}); 
			}
		});
	},
	
	changePassword : function () {

		profile.sessionPing();
		
		$('.popup_bg,.popup').remove();
		$('html').removeClass('popup_open');
		
		ajaxRequest('/profile/changePassword/', '', function(data) {
            
			if (data) {
				$('body').append(data);
			}
			
		});
	},
	
	removeDoctor : function (doctorId, clinicId) {

		profile.sessionPing();

		sendData = {};
		sendData['doctorId'] = doctorId;
		sendData['clinicId'] = clinicId;
		
		ajaxRequest('/profile/removeDoctor/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			if(data.location) {
				window.location.href = data.location;
			}
            
			if (data.ok) {
				location.reload();
			}  
		});
	},
	
	openMessage : function (id) {

		profile.sessionPing();
		
		$('.popup_bg,.popup').remove();
		$('html').removeClass('popup_open');
		
		sendData = {};
		sendData['message'] = id;
		
		ajaxRequest('/profile/openMessage/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			if (data.html) {
				$('body').append(data.html);
				position_popup();
				
				$('.popup .close, .popup_bg').off('click').on('click', function() {
					profile.closePopup();
					location.reload();
				});
			} 
		});
	},
	
	deleteProfile : function () {

		profile.sessionPing();

		$('.popup_bg,.popup').remove();
		$('html').removeClass('popup_open');
		
		sendData = {};
		
		ajaxRequest('/profile/deleteProfile/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			if (data.html) {
				popup_close();
				
				$('body').append(data.html);
				position_popup();
				
				$('.popup .close').click(function() {
					profile.closePopup();
				});
			} 
		});
	},
	
	deleteProfileConfirm : function () {

		profile.sessionPing();

		sendData = {};
		sendData['password'] = $('#password').val();

		ajaxRequest('/profile/deleteProfileConfirm/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			if (data.ok) {
				window.location.href = data.location;
			} else {
				profile.setErrors(data.errors);
			}
		});
	}, 
	
	deleteMessagesCancel : function () {
		this.closePopup();
		profile.messagesToDelete = [];
	},
	
	deletePersonCancel : function () {
		this.closePopup();
		profile.deletePersonId = false;
	},
	
	deletePerson : function (id) {

		profile.sessionPing();
		
		profile.deletePersonId = id;
		
		sendData = {};
		
		ajaxRequest('/profile/deletePerson/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			if (data.html) {
				popup_close();
				
				$('body').append(data.html);
				position_popup();
				
				$('.popup .close').click(function() {
					profile.closePopup();
				});
			} 
		});
	},
	
	deletePersonConfirm : function () {

		profile.sessionPing();
		
		if (profile.deletePersonId) {
			sendData = {};
			sendData['id'] = profile.deletePersonId;
			
			ajaxRequest('/profile/deletePersonConfirm/', sendData, function(data) {

				if(data.logged_off) {
					location.reload();
				}
	            
				if (data.ok) {
					window.location.href = data.location;
				}
			});
		}
		
		
	},
	
	deleteMessage : function (id) {

		profile.sessionPing();

		profile.messagesToDelete = [];
		profile.messagesToDelete.push(id);
		
		sendData = {};
		
		ajaxRequest('/profile/deleteMessageConfirm/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			if (data.html) {
				$('.popup_bg,.popup').remove();
				$('html').removeClass('popup_open');
				
				$('body').append(data.html);
				position_popup();
				
				$('.popup .close').click(function() {
					profile.closePopup();
				});
			} 
		});
	},
	
	deleteMultiMessages : function () {

		profile.sessionPing();

		profile.messagesToDelete = [];

		$('.messages').each(function(n, element) {
			
			if ($(this).attr('type') === 'checkbox') {
				
				if ($(this).is(':checked')) {
					profile.messagesToDelete.push($(this).val());
				}
			}
		});

		if (profile.messagesToDelete.length > 0) {
			sendData = {};
			
			ajaxRequest('/profile/deleteMessageConfirm/', sendData, function(data) {

				if(data.logged_off) {
					location.reload();
				}
	            
				if (data.html) {
					$('.popup_bg,.popup').remove();
					$('html').removeClass('popup_open');
					
					$('body').append(data.html);
					position_popup();
					
					$('.popup .close').click(function() {
						profile.closePopup();
					});
				} 
			});
		}
		
	},
	
	deleteMessages : function () {

		profile.sessionPing();
		
		sendData = {};
		sendData['messages'] = profile.messagesToDelete;
		
		ajaxRequest('/profile/deleteMessage/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			if (data.ok) {
				location.reload();
			} 
		});
	},
	
	saveProfile : function () {

		profile.sessionPing();

		$('.success_msg').hide();
		profile.removeErrors();
		
		sendData = {};
		sendData['action'] = 'save';
		sendData['fields'] = {};

		$('.profile').each(function(n, element) {
			
			if ($(this).attr('name')) {

				console.log($(this).attr('name'));

				if ($(this).attr('type') === 'checkbox') {
					
					if ($(this).is(':checked')) {
						sendData['fields'][$(this).attr('name')] = $(this).val();
					} else {
						sendData['fields'][$(this).attr('name')] = '0';
					}
					
				} else if ($(this).attr('type') === 'radio') {

					if ($(this).is(':checked')) {
						sendData['fields'][$(this).attr('name')] = $(this).val();
					}

				} else {

					if (typeof(sendData['fields'][$(this).attr('name')]) != 'undefined') {
						sendData['fields'][$(this).attr('name')] += '-' + $(this).val();
					} else {
						sendData['fields'][$(this).attr('name')] = $(this).val();
					}
				}
			}
		});

		console.log(sendData);
		
		ajaxRequest('/profile/save/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			if(data.location) {
				window.location.href = data.location;
			}

			if(data.reload) {
				location.reload();
			}

			if(data.confirm_personal_data === 'confirmed') {
				$('#confirm-personal-data').addClass('hidden');
			} else {
				$('#confirm-personal-data').removeClass('hidden');
			}
            
			if (data.errors) {
				$('html,body').animate({scrollTop: $('.col2_y').offset().top});
				profile.setErrors(data.errors);
			} else {
				var $successMsg = $('.success_msg');
				$successMsg.show();
				$('html,body').animate({scrollTop: $successMsg.offset().top});
			}
			
		});
	},
	
	addPerson : function () {

		profile.sessionPing();

		profile.removeErrors();
		
		sendData = {};
		sendData['action'] = 'add';
		sendData['fields'] = {};
		$('.profile').each(function(n, element) {
			
			if ($(this).attr('type') === 'checkbox') {
				
				if ($(this).is(':checked')) {
					sendData['fields'][$(this).attr('id')] = $(this).val();
				} else {
					sendData['fields'][$(this).attr('id')] = '0';
				}
				
			} else if ($(this).attr('type') === 'radio') {
				if ($(this).is(':checked')) {
					sendData['fields'][$(this).attr('name')] = $(this).val();
				}
			} else {

				if (typeof(sendData['fields'][$(this).attr('id')]) != 'undefined') {
					sendData['fields'][$(this).attr('id')] += '-' + $(this).val();
				} else {
					sendData['fields'][$(this).attr('id')] = $(this).val();
				}
				
			}
			
		});
		
		ajaxRequest('/profile/addPerson/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			if (data.errors) {
				profile.setErrors(data.errors);
			} else {
				window.location.href = data.location;
			}
			
		});
	},
	
	profileRegister : function (el) {

		if(el.classList.contains('disabled')) {
			return true;
		}

		el.classList.add('disabled');
		
		profile.removeErrors();
		
		sendData = {};
		sendData['action'] = 'register';
		sendData['fields'] = {};

		$('.register').each(function(n, element) {
			
			if ($(this).attr('type') === 'checkbox') {
				
				if ($(this).is(':checked')) {
					sendData['fields'][$(this).attr('id')] = $(this).val();
				} else {
					sendData['fields'][$(this).attr('id')] = '0';
				}
				
			} else if ($(this).attr('type') === 'radio') {

				if ($(this).is(':checked')) {
					sendData['fields'][$(this).attr('name')] = $(this).val();
				}

			} else {

				if (typeof(sendData['fields'][$(this).attr('id')]) != 'undefined') {
					sendData['fields'][$(this).attr('id')] += '-' + $(this).val();
				} else {
					sendData['fields'][$(this).attr('id')] = $(this).val();
				}
			}
		});

		ajaxRequest('/profile/register/', sendData, function(data) {
            
			if (data.errors) {
				profile.setErrors(data.errors);
				el.classList.remove('disabled');
			} else {
				window.location.href = data.location;
			}
			
		});
		
	},


	changeLang : function (el,lang) {
		sendData = {};
		sendData['action'] = 'changeLang';
		sendData['fields'] = {};
		sendData['lang'] = lang;

		$('.register').each(function(n, element) {

			if ($(this).attr('type') === 'checkbox') {

				if ($(this).is(':checked')) {
					sendData['fields'][$(this).attr('id')] = $(this).val();
				} else {
					sendData['fields'][$(this).attr('id')] = '0';
				}

			} else if ($(this).attr('type') === 'radio') {

				if ($(this).is(':checked')) {
					sendData['fields'][$(this).attr('name')] = $(this).val();
				}

			} else {

				if (typeof(sendData['fields'][$(this).attr('id')]) != 'undefined') {
					sendData['fields'][$(this).attr('id')] += '-' + $(this).val();
				} else {
					sendData['fields'][$(this).attr('id')] = $(this).val();
				}
			}
		});

		ajaxRequest('/profile/register/', sendData, function(data) {

			 window.location.href = data.location;

		});

	},

	profileRegistrationCancel: function(uid) {

		profile.sessionPing();

		if(!uid) {
			return false;
		}

		sendData = {};
		sendData['userId'] = uid;

		ajaxRequest('/profile/registrationCancel/', sendData, function(data) {

			if (data.errors) {
				//
				console.log(data);
			} else if (data.html) {
                $('.popup_bg,.popup').remove();
                $('html').removeClass('popup_open');

                $('body').append(data.html);
                position_popup();

                $('.popup .close, .popup_bg').off('click').on('click', function() {
                    profile.closePopup();
                });
            }
		});
	},

    registrationCancelConfirm: function(uid) {

        sendData = {};
        sendData['userId'] = uid;

        ajaxRequest('/profile/registrationCancelConfirm/', sendData, function(data) {

            if (data.errors) {
                //
                console.log(data);
            } else {
                window.location.href = data.location;
            }
        });
    },
	
	profileLogin : function () {
		
		profile.removeErrors();
		
		sendData = {};
		sendData['action'] = 'login';
		sendData['fields'] = {};
		$('.login').each(function(n, element) {
			
			sendData['fields'][$(this).attr('id')] = $(this).val();
			
		});
		
		ajaxRequest('/profile/login/', sendData, function(data) {

			// console.log(data);
			// return false;
            
			if (data.errors) {
				profile.setErrors(data.errors);
			} else if(data.tfaRequired) {
				tfa.showAuthPopup()
			} else if(data.html) {
				$('body').append(data.html);
				position_popup();
			} else {
				window.location.href = data.location;
			}
			
		});
		
	},
	
	passwordRecovery : function () {
		
		profile.removeErrors();
		
		//if ($('#password_reminder').val()) {
			sendData = {};
			sendData['email'] = $('#password_reminder').val();
			
			ajaxRequest('/profile/passwordRecovery/', sendData, function(data) {
	            
				if (data.errors) {
					profile.setErrors(data.errors);
				} else {
					$('.dt_tab_wrap').find('.field').hide();
					$('#info').hide();
					$('.send').hide();
					$('.forgot').hide();
					$('.sended').show();


					var $infoSubmittedMsg = $('#info_submitted_msg');

					$infoSubmittedMsg.find('.bolded').text($('#password_reminder').val());
					$infoSubmittedMsg.show();
				}
				
			});
		//}
	},
	
	removeErrors : function (el) {

		el = el || null;

		if(el) {
			$(el).removeClass('wrong')
				.siblings('.error_msg')
				.remove();
			$(el).find('.error_msg').remove();
		} else {
			$('.wrong').each(function(n, element) {
				$(this).removeClass('wrong');
			});
			$('.error_msg').remove();
		}
	}, 
	
	setErrors : function (errors) {

		if (typeof(errors.fields) != 'undefined') {
			$.each(errors.fields, function( index, value ) {
				
				if (index === 'date_of_birth') {

					var $birthday = $('.birthday');

					profile.removeErrors($birthday.find('.cselect'));
					
					$birthday.find('.field').append('<div class="error_msg">' + value + '</div>');
					$birthday.find('.col2a').append('<div class="error_msg">' + value + '</div>');
					$birthday.find('.cselect').addClass('wrong');
					
				} else {

					var type = null;
					var $field = $('#' + index);
					if(!$field.length) {
						$field = $('[name=' + index + ']');

						if($field.length) {
							type = ($field.length > 1) ? $($field[0]).attr('type') : $field.attr('type');
						}

					} else {
						type = $field.attr('type');
					}

					if (type === 'text' || type === 'password') {

						if ($field.hasClass('loginhd')) {

							$field.parents('.cinput').after('<div class="error_msg">' + value + '</div>');

						} else if(index === 'captcha_code') {

							$field.parents('.cinput').after('<div class="error_msg">' + value + '</div>');

						} else {

                            if ($field.hasClass('twin')) {

                                profile.removeErrors($field.parents('.separated_inputs').find('.cinput'));

                                $field.parents('.separated_inputs').find('.cinput').addClass('wrong');

                            } else {

                                console.log($field);
                                console.log($field.parents('.cinput'));

                                profile.removeErrors($field.parents('.cinput'));
                                $field.parents('.cinput').addClass('wrong');
                            }
							
							$field.parents('.cinput').after('<div class="error_msg">' + value + '</div>');
						}

					} else if (type === 'radio') {

						var $group = $field.parents('.radio-group');
						profile.removeErrors($group);
						$group.addClass('wrong');
						$group.after('<div class="error_msg">' + value + '</div>');
						
					} else if ($field.is("select")) {

						profile.removeErrors($field.parent());
						$field.parent().addClass('wrong');
						$field.parent().parent().append('<div class="error_msg">' + value + '</div>');
						
						
					} else if (type === 'checkbox') {
						profile.removeErrors($field.parent().parent());
						$field.parent().parent().addClass('wrong');
						$field.parent().parent().append('<div class="error_msg">' + value + '</div>');
					} else {
						if ($field.parent().hasClass('cinput')) {
							
							if ($field.hasClass('twin')) {
								profile.removeErrors($field.parents('.separated_inputs').find('div.cinput'));
								$field.parents('.separated_inputs').find('div.cinput').addClass('wrong');
							} else {
								profile.removeErrors($field.parent());
								$field.parent().addClass('wrong');
							}
						}
						
						$field.parent().parent().append('<div class="error_msg">' + value + '</div>');
					}
				}

				if($field) {
					$field.focus();
				}
			});	
		}
		if (typeof(errors.global) != 'undefined') {

			var $form = $('.form');
			
			if ($form.hasClass('nnn')) {
				$('#email').parent().parent().append('<div class="error_msg">' + errors.global + '</div>');
			} else {
				$form.prepend('<div class="error_msg">' + errors.global + '</div>');
			}
			
		}
	},
	
	setNewPassword : function () {

		profile.sessionPing();
		
		$('.success_msg').hide();
		profile.removeErrors();
		
		sendData = {};
		$('.password').each(function(n, element) {
			
			sendData[$(this).attr('id')] = $(this).val();
			
		});

		// New password should differ from old by at least 40%
		if (sendData.current_password && sendData.current_password.length >= 6 && sendData.password && sendData.password.length >= 9)
		{
			var compareResult = stringSimilarity.compareTwoStrings(sendData.password, sendData.current_password);
			compareResult = Math.round(compareResult * 100);
			var passDiffFromOld = (compareResult < 60) ? true : false;

			if (passDiffFromOld == false)
			{
				profile.setErrors({
					fields: {
						password: messages.new_pass_should_diff_from_old,
					}
				});
				return;
			}
		}
		
		ajaxRequest('/profile/setNewPassword/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}
            
			if (data.errors) {
				profile.setErrors(data.errors);
			} else {
				$('.password').val('');
				$('#info_pass_change_mandatory').hide();
				tfa.tfaOff();
				$('.success_msg').show();
			}
			
		});
	},

	showTemporaryLockedSlots: function () {

		console.log('showTemporaryLockedSlots');
		console.log(profile.temporaryLockedSlots);

		if(profile.temporaryLockedSlots) {
			$.each(profile.temporaryLockedSlots, function( index, value ) {
				if(window.site.ShowOnlyFreeSlots) {
					$(".line[data-id="+value+"]").removeClass('removed_slot');
				} else {
					$(".line[data-id="+value+"]").addClass("shedule green blue black");
				}
			});

			profile.temporaryLockedSlots = null;
		}
	},
	
	addReservationPopup : function (param, isConsultation = false, serviceId = null, dc = false) {

		profile.sessionPing();

		sendData = {};

		if(isConsultation) {

			sendData['doctorId'] = param['doctorId'];
			sendData['clinicId'] = param['clinicId'];
			sendData['isConsultation'] = 1;

		} else {
			sendData['sheduleId'] = param;
		}

		if(dc) {

			sendData['dc'] = true;
			sendData['serviceId'] = serviceId;
		}

		// Ensure we show previously locked slots
		profile.showTemporaryLockedSlots();
		
		ajaxRequest('/profile/addReservationPopup/', sendData, function(data) {

			if (data.location) {
				window.location.href = data.location;
				return;
			}

            if (data.html) {

				profile.showAddReservationPopup(data.html);

				$('#service_id').change();

				if(data.error) {

					console.log( data.error );

					if(data.error.alreadyBooked || data.error.alreadyLocked) {

						var scheduleId = data.error.scheduleId;

						if(window.site.ShowOnlyFreeSlots) {
							$(".line[data-id="+scheduleId+"]").addClass('removed_slot');
						} else {
							$(".line[data-id="+scheduleId+"]").removeClass("shedule green blue black");
						}
					}
				}

				if(data.slotsToBook) {
					$.each(data.slotsToBook, function( index, value ) {
						if(window.site.ShowOnlyFreeSlots) {
							$(".line[data-id="+value+"]").addClass('removed_slot');
						} else {
							$(".line[data-id="+value+"]").removeClass("shedule green blue black");
						}
					});

					profile.temporaryLockedSlots = data.slotsToBook;

					console.log('just placed to temoraryLockedSlots');
					console.log(profile.temporaryLockedSlots);
				}

				$('.popup-add-reservation .close, .popup_bg').off('click').on('click', function() {

					$('.popup-service-info').remove();

					if(!data.noCancelRes) {
						if($('body').find('.popup').hasClass('popup-add-reservation')) {
							profile.cancelAddReservation();
						}
					}
					profile.closePopup();
				});
            }
		});
	},

	finishReservationPopup : function (resId) {

		profile.sessionPing();

		sendData = {
			resId: resId
		};

		ajaxRequest('/profile/finishReservationPopup/', sendData, function(data) {

			console.log( 'finishReservationPopup' );
			console.log( data );

			if (data.location) {
				window.location.href = data.location;
				return;
			}

			if (data.html) {

				profile.showPopup(data.html);

				if(data.error) {

					console.log( data.error );
				}

				// set handler for continue btn
				$('.finishResBtn').off('click').on('click', function () {
					var resId = $('#resId').val();
					profile.closePopup();
					profile.finishReservation(resId);
				});

				// here we also call cancelAddReservation. To mark this reservation cancelled, remove lock and to free slots

				$('.reservation-finish-popup-parent .close, .reservation-finish-popup-parent .cancel-btn, .popup_bg').off('click').on('click', function() {
					profile.closePopup();
				});
			}
		});
	},

	finishReservation: function (resId) {

		profile.sessionPing();

		sendData = {
			resId: resId
		};

		ajaxRequest('/profile/finishReservation/', sendData, function(fdata) {

			if (fdata.location) {
				window.location.href = fdata.location;
				return;
			}

			if(fdata.orderId) {

				profile.closePopup();

				sendData = {
					orderId: fdata.orderId
				};

				ajaxRequest('/profile/showOrderDetailsPopup/', sendData, function(data) {

					if(data.logged_off) {
						location.href = window.location.href.split('?')[0];
					}

					if(data.finalStatus) {
						console.log('Final status: ' + data.finalStatus);
						var newURL = location.href.split("?")[0];
						window.history.pushState('object', document.title, newURL);
						return false;
					}

					if(data.location) {
						window.location.href = data.location;
						return true;
					}

					if(data.error) {
						console.log(data.error);
					}

					if(data.html) {

						// remove query params
						var uri = window.location.toString();
						if (uri.indexOf("?") > 0) {
							// var clean_uri = uri.substring(0, uri.indexOf("?"));
							var clean_uri = removeParam('orderId', uri);
							window.history.replaceState({}, document.title, clean_uri);
						}

						// show popup

						profile.showPopup(data.html);
						position_popup();
						cselect();
						tip();

						data.slots = data.slots.split(',');

						$.each(data.slots, function( index, value ) {
							if(window.site.ShowOnlyFreeSlots) {
								$(".line[data-id="+value+"]").addClass('removed_slot');
							} else {
								$(".line[data-id="+value+"]").removeClass("shedule green blue black");
							}
						});

						profile.handleOrderPopup();

						$('.popup .close, .popup_bg').off('click').on('click', function() {
							var $popup = $('.popup');
							if($popup.hasClass('popup-add-reservation') || $popup.hasClass('order-details-popup-parent')) {
								profile.cancelAddReservation();
							}
							profile.closePopup();
						});

					}

				});

			} else {

				if (fdata.html) {

					profile.showPopup(fdata.html);

					if(fdata.error) {

						console.log( fdata.error );
					}


					$('.reservation-finish-popup-parent .close, .popup_bg').off('click').on('click', function() {
						profile.closePopup();
					});
				}
			}
		});
	},

	backStepOneReservation: function () {

		profile.sessionPing();

		// Set schedule id and locked slots
		sendData = {};
		sendData['sheduleId'] = $('#sheduleId').val();
		sendData['orderId'] = $('#orderId').val();
		sendData['serviceId'] = $('#serviceId').val();
		sendData['slots'] = $('#slots').val();
		sendData['lock_id'] = $('#lock_id').val();
		sendData['reservationId'] = $('#reservation_id').val();
		sendData['personId'] = $('#personId').val();
		sendData['notice'] = $('#note').val();
		sendData['anyTime'] = $('#anyTime').val();
		sendData['selectedTime'] = $('#selectedTime').val();

		var dc = $('#dc').val();
		var insurance = $('#insurance').val();

		if(dc) {
			sendData['dc'] = dc;
		}

		if(insurance) {
			sendData['insurance'] = insurance;
		}

		// fix back -- fromTSWidget flag shows if popup was opened via Consultation button, not via schedule calendar
		// so if it is set to 1 value, we should show to user TS Widget popup
		sendData['isConsultation'] =  $('#fromTSWidget').val();

		var $resOpts = $('.resOptionsHiddenInput');

		if($resOpts.length) {

			$resOpts.each(function () {

				sendData[$(this).attr('id')] = $(this).val();
			});
		}

		ajaxRequest('/profile/backToReservationPopup/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			if (data.html) {
				profile.closePopup();
				profile.showAddReservationPopup(data.html);

				// Ensure we show previously locked slots
				profile.showTemporaryLockedSlots();

				if(sendData['slots']) {
					var slotsArray = sendData['slots'].split(',');

					$.each(slotsArray, function( index, value ) {
						if(window.site.ShowOnlyFreeSlots) {
							$(".line[data-id="+value+"]").removeClass('removed_slot');
						} else {
							$(".line[data-id="+value+"]").removeClass("shedule green blue black").addClass('line shedule blue');
						}
					});
				}

				if(data.serviceId) {
					$('select#service_id').val(data.serviceId).trigger('change').selectric('refresh');
					cselect();
				}

				if($('[name="popup_attendee"]:checked').val() === 'other') {
					$('.popup_attendee_select input[name=popup_attendee]').trigger('change');
				}

				$('.popup .close, .popup_bg').off('click').on('click', function() {
					profile.cancelAddReservation();
					profile.closePopup();
				});
			}
		});
	},

	backStepOneConsultation: function() {

		var data = {
			doctorId: $('#doctor_id').val(),
			clinicId: $('#clinic_id').val(),
			consultationId: $('#consultation_id').val()
		};

		profile.closePopup();
		profile.addConsultationPopup(data);
	},

	showPopup: function(html) {
		$('body').append(html);
		position_popup();
	},

	showAddReservationPopup: function(content, serviseSelected) {
		serviseSelected = serviseSelected || null;
		profile.showPopup(content);
		popup_client_select();
		auth_gender();
		cselect();
		tip();
		checkboxes();

		doctors_open_slider();
		doctors_open_slider_resize();
		doctors_open_moreinfo();

		doctors_list_line();
		doctors_list_line();

		// if speech api available we init voice input on notice field

		if(window.voiceInputAvailable) {

			profile.noticeVoiceInput = set_voice_input(document.querySelector('textarea#notice'));
		}

		// Using select plugin Selectric
		$('#service_id').selectric({
			arrowButtonMarkup: '<b class="button">&#x25b2;</b>'
		});

		$('#voiceLangSelect').selectric({
			arrowButtonMarkup: '<b class="button">&#x25b2;</b>'
		});

		$('.popup .close').off('click').on('click', function () {
			profile.setReservationInProgress();
			profile.closePopup();
		});
	},

	performPayment: function () {

		profile.sessionPing();

		profile.showSpinner(messages.wait_connectingPaymentSystem);

		sendData = {};
		sendData['reservationId'] = $('#reservation_id').val();
		sendData['consultationId'] = $('#consultation_id').val();
		sendData['orderId'] = $('#orderId').val();
		sendData['backUrl'] = window.location.href;
		sendData['calendarData'] = profile.collectCalendarData();
		sendData['fields'] = {};
		sendData['fields']['order-agree'] = $('#order-agree').is(':checked') ? 1 : 0;
		sendData['fields']['method'] = $('[name=method]:checked').val();

		// other data for payment transaction
		ajaxRequest('/profile/performPayment/', sendData, function(data) {

			// normally we receive payment url to go to
			if(data.location) {
				window.location.href = data.location;
				return true;
			}

			profile.hideSpinner();

			if(data.logged_off) {
				location.reload();
			}

			if(data.errors) {
				console.log(data.errors);
				// error handling
				profile.setErrors(data.errors);
			}

			// billing system error handling
			if(data.error) {

				console.error('Gateway error...');

				if(data.debug) {
					console.log(data.debug);
				}

				var $popupCommunicationError = $('.popup-communication-error');

				$popupCommunicationError.addClass('active');

				$('.popup-communication-error .close, .close_message a').off('click').on('click', function () {
					$popupCommunicationError.removeClass('active');
				});
			}
		});
	},

	setCalendarDataToSession: function() {
		sendData = {};
		sendData['calendarData'] = profile.collectCalendarData();

		ajaxRequest('/profile/setCalendarDataToSession/', sendData, function(data) {});
	},

	// collect restore data for calendar
	collectCalendarData: function() {

		var cData = {};
		cData.paymentTypes = {};

		$('.calendar_filter').each(function (n, element) {

			// get payment type checkboxes state
			if ($(this).attr('type') === 'checkbox') {
				cData.paymentTypes[$(this).attr('rel')] = $(this).is(':checked');
			}
		});

		// get date
		cData.date = $("#calendar_list_header").find('div.days div.cell').first().attr('data-id');
		cData.scrollPos = window.pageYOffset;

		return cData;
	},
	
	cancelAddReservation : function () {

		profile.sessionPing();

		sendData = {};
		sendData['lockId'] = $('#lock_id').val();
		sendData['slots'] = $('#slots').val();

		if($('#dcAppointment')) {
			sendData['dcAppointment'] = $('#dcAppointment').val();
		}

		// if cancel from order info
		if($('.popup').hasClass('order-details-popup-parent')) {
			sendData['sheduleId'] = $('#sheduleId').val();
			sendData['orderId'] = $('#orderId').val();
			sendData['reservationId'] = $('#reservation_id').val();
		}

		// canceled from paymentCancel Page
		if($('.payment_cancel_block').length || $('.payment_fail_block').length) {
			sendData['orderId'] = $('#order_id').val();
			sendData['backUrl'] = $('#back_url').val();
		}

		// Ensure we show previously locked slots
		profile.showTemporaryLockedSlots();

		ajaxRequest('/profile/cancelAddReservation/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			if(data.finalStatus) {
				console.log('Final status: ' + data.finalStatus);
				var newURL = location.href.split("?")[0];
				window.history.pushState('object', document.title, newURL);
				return false;
			}

			if(data.backUrl) {

				// if we cancelling add reservation, we don't need to return to order popup
				// so return to first part (without q params) of back url (if passed)

				location.href = data.backUrl.split("?")[0];
			}

			if(data.canceled) {

				profile.closePopup();

				if(data.slotsToUnbook) {

					$.each(data.slotsToUnbook, function( index, value ) {

						var $currShedule = $(".line[data-id="+value+"]");

						if(window.site.ShowOnlyFreeSlots) {
							$currShedule.removeClass('removed_slot');
						} else {
							$currShedule.removeClass("shedule green blue black").addClass('shedule ' + $currShedule.data('color-class'));
						}
					});
				}

				if(sendData['slots']) {
					var slotsArray = sendData['slots'].split(',');

					$.each(slotsArray, function( index, value ) {
						var $currShedule = $(".line[data-id="+value+"]");

						if(window.site.ShowOnlyFreeSlots) {
							$(".line[data-id="+value+"]").removeClass('removed_slot');
						} else {
							$currShedule.removeClass("shedule green blue black").addClass('shedule ' + $currShedule.data('color-class'));
						}
					});
				}

				return true;
			}
		});

		this.setReservationInProgress();
	},

	closePopup: function() {

		if(profile.noticeVoiceInput) {
			profile.noticeVoiceInput = null;
		}

		$('.popup .close, .popup_bg', '.popup_bg_session').off('click');
		$('.popup_bg, .popup_bg_session, .popup, .popup-service-info').remove();
		$('html').removeClass('popup_open');
		$(window).trigger('popup_close');
		$('.popup-communication-error').removeClass('active');
	},
	
	addReservation: function (sheduleId) {

		profile.sessionPing();
		profile.removeErrors();

		var lockId = $('#lock_id').val(),
			isConsultation = $('#is_consultation').val(),
			selectedTime = null,
			anyTime = false,
			$insHeaderBlock = $('.insPopupHeaderBlock');

		sendData = {};
		sendData['isConsultation'] = isConsultation;

		if(isConsultation) {

			selectedTime = $('#selected_time').val();

			if(selectedTime && selectedTime !== '*') {
				sendData['scheduleId'] = $('#schedule_id').val();
			} else {
				anyTime = true;
			}

			sendData['selectedTime'] = selectedTime;
			sendData['clinicId'] = $('#clinic_id').val();
			sendData['doctorId'] = $('#doctor_id').val();
		}

		sendData['serviceId'] = profile.reservationProgress.serviceId;
		sendData['lockId'] = lockId;
		sendData['lockedSlots'] = $('#slots').val();
		sendData['fromTSWidget'] = $('#fromTSWidget').val();


		if(profile.reservationProgress.isCountryPaid === 2) {
			sendData['country_agreement'] = 1;
		} else {
			sendData['country_agreement'] = $('#country_agreement').is(':checked') ? 1 : 0;
		}

		sendData['notice'] = $('#notice').val();

		if ($('input[name=popup_attendee]:checked').val() === 'other') {

			var $profilePersonId = $('#profile_person_id');
			
			if ($profilePersonId.val()) {
				sendData['profile_person_id'] = $profilePersonId.val();
			} else {

				if($('.form_addnew').is(':visible')) {
					sendData['profile_person_id'] = 'add';

					sendData['fields'] = {};
					$('.person').each(function(n, element) {


						if ($(this).attr('type') === 'checkbox') {

							if ($(this).is(':checked')) {
								sendData['fields'][$(this).attr('id')] = $(this).val();
							} else {
								sendData['fields'][$(this).attr('id')] = '0';
							}

						} else if ($(this).attr('type') === 'radio') {

							if ($(this).is(':checked')) {

								sendData['fields'][$(this).attr('name')] = $(this).val();
							}
						} else {

							if (typeof(sendData['fields'][$(this).attr('id')]) != 'undefined') {
								sendData['fields'][$(this).attr('id')] += '-' + $(this).val();
							} else {
								sendData['fields'][$(this).attr('id')] = $(this).val();
							}
						}
					});
				} else {
					// add error to profile_person_id select
					profile.setErrors({
						fields: {
							profile_person_id: 'Lūdzu izvēlies personu vai nospied "Pievienot jaunu personu"!'
						}
					});
					return false;
				}
			}
			
		} else if($('input[name=popup_attendee]:checked').val() === 'inTheNameOfPatient') {
			sendData['inTheNameOfPatient'] = $('#appointmentInTheNameOfPatient').val();
		} else {
			sendData['person_id'] = '';
		}

		// check for dc params

		if($('#dcAppointment').length) {
			sendData['dc'] = $('#dcAppointment').val();
		}

		if($('#dc_channel_type').length) {
			sendData['dc_channel_type'] = $('#dc_channel_type').val();
		}

		if($('#dc_entity_name').length) {
			sendData['dc_entity_name'] = $('#dc_entity_name').val();
		}

		if($('#dc_for_kid').length) {
			sendData['dc_for_kid'] = $('#dc_for_kid').val();
		}

		if($('#dc_phone_number').length) {
			sendData['dc_phone_number'] = $('#dc_phone_number').val();
		}

		if($('#dc_consultation_type').length) {
			sendData['dc_consultation_type'] = $('#dc_consultation_type').val();
		}

		if($('#dc_preffered_langs').length) {
			sendData['dc_preffered_langs'] = $('#dc_preffered_langs').val();
		}

		if($('#dc_lang').length) {
			sendData['dc_lang'] = $('#dc_lang').val();
		}

		if($('#dc_services_list').length) {
			sendData['dc_services_list'] = $('#dc_services_list').val();
		}

		sendData['haveInsurance'] = $('#insuranceAllowed').val();
		sendData['needLocalInsuranceCheck'] = $('#needLocalIncuranceCheck').val();

		// if we have reservation id in popup we should pass it as param

		var $resHid = $('#reservation_id');

		if($resHid.length && $resHid.val()) {
			sendData['resId'] = $resHid.val()
		}

		var timeout = + $('#sm_confirmation_timeout').val();

		profile.showSpinner($insHeaderBlock.length ? null : messages.wait_pleaseWait);
		
		ajaxRequest('/profile/addReservation/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			if (data.location) {
				window.location.href = data.location;
				return true;
			}

			// Ensure we show previously locked slots
			profile.showTemporaryLockedSlots();

			if (data.errors) {
				profile.hideSpinner();
				profile.setErrors(data.errors);
				return false;
			}

			if(data.warning_html) {
				$('.popup-add-reservation .cont').html(data.warning_html);
				position_popup();
				profile.hideSpinner();
				return false;
			}

			if(data.insurance_popup_html && data.insurance_start_html) {

				$('.popup_bg, .popup').remove();
				$('html').removeClass('popup_open');

				$('body').append(data.insurance_popup_html);

				$('.popup-add-insurance .cont').html(data.insurance_start_html);

				position_popup();
				cselect();
				tip();

				// handle insurance popup

				profile.handleInsurancePopup();
			}

			// GO AHEAD -- called if confirmed / autoconfirmed status set
			function dataHandle() {

				if (data.html) {
					$('.popup_bg, .popup').remove();
					$('html').removeClass('popup_open');

					if(data.createVroom && data.createVroom.result) {

						try {
							console.log(JSON.parse(data.createVroom.result));
						} catch (e) {
							var newWindow = window.open();
							newWindow.document.write(data.createVroom.result);
						}
					}

					$('body').append(data.html);
					position_popup();
					cselect();
					tip();

					if(data.inTheNameResultPopup) {
						$('.popup .close, .popup_bg, .in-the-name-result .cancel .cancel-btn').off('click').on('click', function() {
							profile.closePopup();
						});
						return true;
					}

					if(data.slots) {

						$.each(data.slots, function( index, value ) {
							if(window.site.ShowOnlyFreeSlots) {
								$(".line[data-id="+value+"]").addClass('removed_slot');
							} else {
								$(".line[data-id="+value+"]").removeClass("shedule green blue black");
							}
						});
					}

					profile.handleOrderPopup();

					$('.popup .close, .popup_bg').off('click').on('click', function() {
						var $popup = $('.popup');
						if($popup.hasClass('popup-add-reservation') || $popup.hasClass('order-details-popup-parent')) {
							profile.cancelAddReservation();
						} else {
							profile.closePopup();
							//profile.filterReservationCalendar(null, null, false);
						}
					});
				}
			}

			// SHOW ERROR -- called if non-confirmed status set
			function showError(message) {

				var $reservationPopupCont = $('.reservation-popup-content'),
					$lockId = $('#lock_id').val(),
					$slots = $('#slots').val(),
					scheduleId = $('#schedule_id').val();

				$reservationPopupCont.addClass('error-popup');

				message = message || messages.selectOtherTime;

				var html = "<h2>" + messages.error + "</h2><hr><div class='line w2'";
				html += "<h3>" + message + "</h3></div>";
				html += "<div class='btn_cont'>";
				html += "<div class='back-to-addResrvation'><a class='btng w1' href>" + messages.goBack + "</a></div>";
				html += "<div class='cancel'><a href>" + messages.close + "</a></div></div>";

				if(data.error && data.error.slotToBook) {
					$(".line[data-id="+data.error.slotToBook+"]").addClass('removed_slot');
				}

				$reservationPopupCont.html(html);
				position_popup();

				$('.popup_bg, .popup .close')
					.add($reservationPopupCont.find('.btn_cont a'))
					.off('click')
					.on('click', function (e) {
						e.preventDefault();
						e.stopPropagation();
						var $this = $(this);
						$reservationPopupCont.removeClass('error-popup');
						profile.closePopup();

						var src = data.orderInfo ? data.orderInfo : data.error;

						var sheduleId = src.sheduleId || null,
							lockId = src.lockId || null,
							slots = src.slots || null,
							reservationId = src.reservationId || null,
							orderId = src.orderId || null;

						if(!data.dontCancelReservation) {
							profile.clearReservationData(sheduleId, lockId, slots, reservationId, orderId);
						}

						if($this.parents('.back-to-addResrvation').length && sheduleId) {
							profile.addReservationPopup(scheduleId);
						}
				});
			}

			// handle check result
			function afterCheck(status) {

				if(!status) {
					// if SM not confirmed reservation...
					// SHOW ERROR!
					showError(data.error.message);
					return;
				}

				if(!anyTime) {
					profile.setLockStatus(lockId, status);
				}

				clearTimeout(profile.waitTimer);
				profile.waitTimer = null;

				// if SM not confirmed reservation...
				if(parseInt(status) === profile.lockStatuses.nonConfirmed) {
					// SHOW ERROR!
					showError();
					return;
				}

				// GO AHEAD!
				dataHandle();
			}

			if(data.inTheNameResultPopup) {
				dataHandle();
				return;
			}

			var smCheckNeeded = $('#check_sm').val();

			// if no check necessary, we just set status to confirmed and go ahead
			if(!smCheckNeeded || !anyTime) {
				profile.setLockStatus(lockId, profile.lockStatuses.confirmed);
				dataHandle();
				return;
			}

			if(data.error) {
				afterCheck(false);
				setTimeout(function () {
					profile.hideSpinner();
				}, 100);
				return;
			}

			// if we already get seponse from SM - handle it and return
			if(data.SMCheckResult.confirmed) {
				afterCheck(data.SMCheckResult.confirmed);
				setTimeout(function () {
					profile.hideSpinner();
				}, 100);
				return;
			}

			// if don't get response from SM...
			var checkSMData = {};
			checkSMData['slots'] = data.orderInfo.slots;
			checkSMData['lockId'] = data.orderInfo.lockId;
			checkSMData['reservationId'] = data.orderInfo.reservationId;
			checkSMData['timeout'] = (timeout / 1000); // pass it in seconds

			var posting = $.post( '/profile/checkSM/', checkSMData );

			posting.done(function (checkResult) {
				if(checkResult && checkResult.response && checkResult.response.success) {
					profile.SMcheckResult = checkResult.response;
					afterCheck(checkResult.response.status);
				}
			});

			posting.fail(function (error) {
				afterCheck(profile.lockStatuses.autoconfirmed);
			});

			posting.always(function (alwaysResp) {
				setTimeout(function () {
					profile.hideSpinner();
				}, 100);
			});

			profile.waitTimer = setTimeout(function () {
				profile.hideSpinner();
				afterCheck(profile.lockStatuses.autoconfirmed);
			}, timeout + 500);
		});
	},

	handleInsurancePopup() {

		var $editIns = $('.popup-add-insurance .insurance_edit_form'),
			$insUseChk = $('#ins-use'),
			$insAgreeChk = $('#ins-agree'),
			$close = $('.popup-add-insurance .close, .popup_bg'),
			$backBtn = $('.popup-add-insurance .back-btn'),
			$contBtn = $('.popup-add-insurance .continue-btn'),
			$contPayBtn = $('.popup-add-insurance .continue-btn-pay'),
			$cancelBtn = $('.popup-add-insurance .cancel-btn'),
			needLocalInsuranceCheck = $('#needLocalInsuranceCheck').val() === '1';

		checkboxes();

		$editIns.on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();

			profile.showInsuranceEditForm();
		});

		$insAgreeChk.on('change', function(e) {

			$contBtn.toggleClass('disabled', !($insAgreeChk.is(':checked')));
		});

		$insUseChk.on('change', function(e) {

			if(needLocalInsuranceCheck) {
				$(this).prop('checked', false).attr('disabled', true).parents('label.item').removeClass('checked');
				$('.needLocalInsuranceCheck').show();
			}
		});

		$close.add($cancelBtn).on('click', function (e) {
			profile.cancelAddReservation();
		});

		$backBtn.on('click', function(e) {
			profile.backStepOneReservation();
		});

		$contBtn.on('click', function(e) {

			e.preventDefault();
			e.stopPropagation();

			if(!$contBtn.hasClass('disabled')) {

				if($insUseChk.is(':checked') && !needLocalInsuranceCheck) {
					// continue to check insurance
					profile.checkInsurance();
				} else {
					// continue payment
					profile.addReservation($('#sheduleId').val());
				}
			}
		});

		$contPayBtn.on('click', function (e) {

			e.preventDefault();
			e.stopPropagation();

			profile.addReservation($('#sheduleId').val());
		});
	},

	showInsuranceEditForm() {

		sendData = {};

		var $hiddens = $('.popup-add-insurance .ins_hidden_inp');

		$hiddens.each(function (n, el) {
			sendData[el.id] = $(el).val();
		});

		ajaxRequest('/profile/showInsuranceEditForm/', sendData, function(data) {

			if(data.insurance_edit_html) {

				$('.popup-add-insurance .cont').html(data.insurance_edit_html);

				position_popup();
				cselect();
				tip();

				// add plugin dp and btn handlers

				var $inps = $('.popup-add-insurance input, .popup-add-insurance select'),
					$save = $('.popup-add-insurance .save-btn'),
					$back = $('.popup-add-insurance .back-to-ins-first-btn'),
					$dpIns = $('.popup-add-insurance .jq-calend');

				if($dpIns.length) {

					$.each($dpIns, function (key, el) {
						setInsDatepicker(el, null, true);
					});
				}

				$inps.on('change input select', function (e) {
					$save.removeClass('disabled');
				});

				$(window).on('resize', function () {

					$('.popup-add-insurance .hasDatepicker')
						.datepicker('hide')
						.datepicker('option', 'disabled', true)
						.datepicker('option', 'disabled', false);
				});

				$save.on('click', function (e) {
					e.preventDefault();
					e.stopPropagation();

					if(!$save.hasClass('disabled')) {
						profile.saveInsuranceData();
					}
				});

				$back.on('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					profile.showInsuranceFirst();
				});
			}
		});
	},

	showInsuranceFirst() {

		var $hiddens = $('.popup-add-insurance .ins_hidden_inp');

		$hiddens.each(function (n, el) {
			sendData[el.id] = $(el).val();
		});

		ajaxRequest('/profile/showInsuranceFirst/', sendData, function(data) {

			if(data.insurance_start_html) {

				$('.popup-add-insurance .cont').html(data.insurance_start_html);

				position_popup();
				cselect();
				tip();

				// handle insurance popup

				profile.handleInsurancePopup();
			}
		});
	},

	saveInsuranceData() {

		var $hiddens = $('.popup-add-insurance .ins_hidden_inp');

		$hiddens.each(function (n, el) {
			sendData[el.id] = $(el).val();
		});

		sendData['insurance_number'] = $('#insurance_number').val();
		sendData['insurance_id'] = $('#insurance_id').val();
		sendData['insurance_start_date'] = $('#ins-from-jqui-calendar').val();
		sendData['insurance_end_date'] = $('#ins-to-jqui-calendar').val();
		sendData['lockId'] = $('#lock_id').val();

		ajaxRequest('/profile/saveInsuranceData/', sendData, function(data) {

			if(data.insurance_start_html) {

				$('.popup-add-insurance .cont').html(data.insurance_start_html);

				position_popup();
				cselect();
				tip();

				// handle insurance popup

				profile.handleInsurancePopup();
			}
		});
	},

	checkInsurance() {

		var $header = $('.ins_header'),
			$headerChecking = $('.ins_header_checking'),
			$spinnerCont = $('.popup-add-insurance .spinner-container');

		$header.hide();
		$headerChecking.show();
		$spinnerCont.show();

		console.log('CheckInsurance called');

		var $hiddens = $('.popup-add-insurance .ins_hidden_inp');

		$hiddens.each(function (n, el) {
			sendData[el.id] = $(el).val();
		});

		ajaxRequest('/profile/checkInsurance/', sendData, function(data) {

			console.log('CheckInsurance response:');
			console.log(data);

			$headerChecking.hide();
			$header.show();
			$spinnerCont.hide();

			if(data.insurance_result_html) {

				$('.popup-add-insurance .cont').html(data.insurance_result_html);

				position_popup();
				cselect();
				tip();

				var $editIns = $('.popup-add-insurance .insurance_edit_form'),
					$insUseChk = $('#ins-use'),
					$insAgreeChk = $('#ins-agree'),
					$close = $('.popup-add-insurance .close, .popup_bg'),
					$backBtn = $('.popup-add-insurance .back-to-ins-first-btn'),
					$contBtn = $('.popup-add-insurance .continue-btn'),
					$contPayBtn = $('.popup-add-insurance .continue-btn-pay'),
					$cancelBtn = $('.popup-add-insurance .cancel-btn');

				$editIns.on('click', function (e) {
					e.preventDefault();
					e.stopPropagation();

					profile.showInsuranceEditForm();
				});

				$insUseChk.add($insAgreeChk).on('change', function(e) {

					$contBtn.toggleClass('disabled', !($insAgreeChk.is(':checked')));
				});

				$close.add($cancelBtn).on('click', function (e) {
					profile.cancelAddReservation();
				});

				$backBtn.on('click', function(e) {
					profile.backStepOneReservation();
				});

				$contBtn.on('click', function(e) {

					e.preventDefault();
					e.stopPropagation();

					if(!$contBtn.hasClass('disabled')) {

						if($insUseChk.is(':checked')) {
							// continue to check insurance
							profile.checkInsurance();
						} else {
							// continue payment
							profile.addReservation($('#sheduleId').val());
						}
					}
				});

				$contPayBtn.on('click', function (e) {

					e.preventDefault();
					e.stopPropagation();

					profile.addReservation($('#sheduleId').val());
				});
			}
		});
	},

	clearReservationData: function(sheduleId, lockId, slots, reservationId, orderId) {

		profile.sessionPing();

		var clearResData = {};
		clearResData['sheduleId'] = sheduleId;
		clearResData['lockId'] = lockId;
		clearResData['slots'] = slots;
		clearResData['reservationId'] = reservationId;
		clearResData['orderId'] = orderId;

		ajaxRequest('/profile/clearReservationData/', clearResData, function (data) {});
	},

	setLockStatus: function(lockId, status, reservationId, hspReservationId) {

		if(!lockId) {
			return false;
		}

		profile.sessionPing();

		reservationId = reservationId || null;
		hspReservationId = hspReservationId || null;

		var lockStatusData = {};
		lockStatusData['lockId'] = lockId;
		lockStatusData['status'] = status;
		lockStatusData['reservationId'] = reservationId;
		lockStatusData['hspReservationId'] = hspReservationId;
		ajaxRequest('/profile/setLockStatus/', lockStatusData, function(lockStatusResult) {});
	},

	restoreCalendar: function(cData) {

		$('.calendar_filter').each(function (n, element) {

			// restore payment type checkboxes state
			if ($(this).attr('type') === 'checkbox') {

				if(cData.paymentTypes[$(this).attr('rel')] === 'true') {
					$(this).prop({
						checked: true
					});
					$(this).parents('label').addClass('checked');
				} else {
					$(this).prop({
						checked: false
					});
					$(this).parents('label').removeClass('checked');
				}


			}
		});

		setTimeout(function () {

			// call filter function
			profile.wfilterReservationCalendar('search', cData.date, false);
			window.scrollTo(0, cData.scrollPos);
		}, 50);

	},
	
	filterReservationCalendar : function(type, date, showSpinner) {

		showSpinner = showSpinner || true;
		var $doctorListElement = $('.dclist');

		// show spinner
		if(showSpinner && $doctorListElement.length > 10) {
			profile.showSpinner(messages.wait_pleaseWait, 'calendar');
		}

		type = type || 'search';

		sendData = {};
		sendData['queryString'] = location.search.replace('?', '');
		sendData['payment_type'] = {};
		sendData['remote_services'] = false;
		sendData['subscription'] = false;
		sendData['dcDoctors'] = false;
		sendData['type'] = type;
		
		if (profile.doctorId !== '' && profile.clinicId) {
			
			sendData['doctorId'] = profile.doctorId;
			sendData['clinicId'] = profile.clinicId;
			
		} else {

			sendData['doctorId'] = {};
			sendData['clinicId'] = {};
			
			$doctorListElement.each(function(n, element) {
				
				if (typeof(sendData['doctorId'][$(this).attr('data-doctor-id')]) == 'undefined') {
					sendData['doctorId'][$(this).attr('data-doctor-id')] = [];
				}
				
				sendData['doctorId'][$(this).attr('data-doctor-id')].push($(this).attr('data-clinic-id'));
			});
		}

		if ($('div.slide_3').is(":visible")) {

			if (type === 'next') {
				sendData['lastDate'] = $("div.slide_3").find('div.days div.cell').last().attr('data-id');
				sendData['days'] = 7;
			} else {
				sendData['lastDate'] = $("div.slide_3").find('div.days div.cell').first().attr('data-id');
				sendData['days'] = 7;
			}

		} else if ($('.slide_2').is(":visible")) {

			if (type === 'next') {
				sendData['lastDate'] = $("div.slide_2").find('div.days div.cell').last().attr('data-id');
				sendData['days'] = 10;
			} else {
				sendData['lastDate'] = $("div.slide_2").find('div.days div.cell').first().attr('data-id');
				sendData['days'] = 10;
			}

		} else if ($('div.slide_1').is(":visible")) {

			if (type === 'next') {
				sendData['lastDate'] = $("div.slide_1").find('div.days div.cell').last().attr('data-id');
				sendData['days'] = 14;
			} else {
				sendData['lastDate'] = $("div.slide_1").find('div.days div.cell').first().attr('data-id');
				sendData['days'] = 14;
			}

		} else {

			if (type === 'next') {
				sendData['lastDate'] = $("#calendar_list_header").find('div.days div.cell').last().attr('data-id');
				sendData['days'] = 7;
			} else if(type === 'setFilter'){
				sendData['lastDate'] = date;
				sendData['days'] = 7;

			} else {
				sendData['lastDate'] = $("#calendar_list_header").find('div.days div.cell').first().attr('data-id');
				sendData['days'] = 7;
			}
		}

		if (typeof(date) != 'undefined') {
			sendData['lastDate'] = date;
		}

		$("#filter_date").val(sendData['lastDate'])

		$('.calendar_filter').each(function(n, element) {

			if ($(this).attr('type') === 'checkbox') {
				
				if ($(this).is(':checked')) {

					if($(this).attr('name') === 'remote_services') {
						sendData['remote_services'] = true;
					} else if ($(this).attr('name') === 'subscription') {
							sendData['subscription'] = true;
					} else if ($(this).attr('name') === 'dcDoctors') {
						sendData['dcDoctors'] = true;
					} else {
						sendData[$(this).attr('name')][$(this).attr('rel')] = true;
					}
				} 
			}
		});

		profile.reservationsFilter = sendData;

		ajaxRequest('/doctors/filterReservations/', sendData, function(data) {

			// hide spinner
			if($('.spinner-container-calendar').hasClass('active')) {
				profile.hideSpinner('calendar');
			}

			var $resCalendar = $('#reservation_calendar'),
				$calendarListHeader = $('#calendar_list_header');

            if (data.html) {
            	
                if($resCalendar.length){
					$resCalendar.html(data.html);
                }
                
                if($('.doctors_open').length){
                    doctors_open_slider();
                    doctors_open_slider_resize();
                    doctors_open_moreinfo();
                }
            	
                if($('.doctors_list').length){
					doctors_list_line();
					doctors_list_line();
                }
    			
            } else {

            	if (data.html_header) {
            		
					if($calendarListHeader.length){
						$calendarListHeader.html(data.html_header).parents('.wrap').show();
					}
            		
            		$.each(data.html_body, function(index, value) {
            			$('#' + index).html(value);
            		});
            		
            		if($('.doctors_open').length) {
						doctors_open_slider();
						doctors_open_slider_resize();
						doctors_open_moreinfo();
					}
                	
					if($('.doctors_list').length) {
						doctors_list_line();
						doctors_list_line();
					}
            	}
            }
		});
	},
	
	pdfReservation : function (reservationId) {

		profile.sessionPing();

		sendData = {};
		sendData['reservationId'] = reservationId;
		
		$.ajax({
			type: 'POST',
		    url: '/profile/pdfReservation/',
		    dataType: 'json',
		    data : sendData,
		    async: false,
		    success: function(data) {

				if(data.logged_off) {
					location.reload();
				}

		    	if (data.location) {
					 var win = window.open(data.location, '_blank');
					 win.focus();
				} 
		    }
		});
	},
	
	resendActivationLink : function() {

		profile.sessionPing();

		ajaxRequest('/profile/resendActivationLink/', {}, function(data) {
			//console.log(data);
		});
	},

	openOrder: function(orderId) {

		profile.sessionPing();

		$('.popup_bg, .popup').remove();
		$('html').removeClass('popup_open');

		sendData = {};
		sendData['orderId'] = orderId;

		ajaxRequest('/profile/openOrder/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			$('body').append(data.html);
			position_popup();
			tip();

			$('.popup .close').on('click', function() {
				profile.closePopup();
			});

		});
	},

	openInvoice: function(orderId) {

		profile.sessionPing();

		$('.popup_bg, .popup').remove();
		$('html').removeClass('popup_open');

		sendData = {};
		sendData['orderId'] = orderId;

		ajaxRequest('/profile/openInvoice/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			$('body').append(data.html);
			position_popup();
			tip();

			$('.popup .close').on('click', function() {
				profile.closePopup();
			});

		});
	},

	// DEBUG route
	generatePDF: function(orderId) {

		profile.sessionPing();

		sendData = {};
		sendData['orderId'] = orderId;

		ajaxRequest('/profile/testPdfInvoice/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			console.log('testPdfInvoice');
			console.log(data);
		});
	},

	agreeTerms: function () {

		profile.sessionPing();

		sendData = {};
		sendData['action'] = 'agree_terms';
		sendData['agreement'] = $('#agreement').is(':checked') ? 1 : 0;
		sendData['privacy_policy'] = $('#privacy_policy').is(':checked') ? 1 : 0;

		if(profile.agreeTermsValidate(sendData)) {

			$.ajax({
				type: 'POST',
				url: '/profile/agreeTermsSave/',
				dataType: 'json',
				data : sendData,
				async: false,
				success: function(data) {

					if(data.logged_off) {
						location.reload();
					}

					if(data.location) {
						window.location.href = data.location;
					}

					if(data.errors) {

						$('#continue-btn').addClass('disabled');

						$.each(data.errors, function( index, value ) {
							if(value === false) {
								$('#' + index).parent().parent()
									.addClass('wrong')
									.append('<div class="error_msg">' + 'Please check this field' + '</div>');
							}
						});
					}
				}
			});
		}
	},

	agreeTermsValidate: function (params) {
		return params.agreement && params.privacy_policy;
	},

	arstiemAddDoctor: function () {
		sendData = {};
		sendData['action'] = 'arstiemAddDoctor';
		sendData['full_name'] = $('#full_name').val();
		sendData['a_email'] = $('#a_email').val();
		sendData['phone'] = $('#phone').val();
		sendData['captcha_code'] = $('#captcha_code').val();

		$.ajax({
			type: 'POST',
			url: '/profile/arstiemAddDoctor/',
			dataType: 'json',
			data : sendData,
			success: function(data) {

				if(data.location) {
					window.location.href = data.location;
				}

				if(data.errors) {
					$('.captcha-field input').val('');
					$('.captcha-block a').trigger('click');
					profile.setErrors(data.errors);
				}

				if(data.html) {
					$('.arstiem-block').html(data.html);
				}
			},
			fail: function (error) {
				console.log(error);
			}
		});
	},

	piesakiArstuAddDoctor: function () {

		profile.sessionPing();

		sendData = {};
		sendData['action'] = 'piesakiArstuAddDoctor';
		sendData['doctor_name'] = $('#doctor_name').val();
		sendData['specialty'] = $('#specialty').val();
		sendData['clinic'] = $('#clinic').val();
		sendData['note'] = $('#note').val();

		$.ajax({
			type: 'POST',
			url: '/profile/piesakiArstuAddDoctor/',
			dataType: 'json',
			data : sendData,
			success: function(data) {

				if(data.location) {
					window.location.href = data.location;
				}

				if(data.errors) {
					profile.setErrors(data.errors);
				}

				if(data.html) {
					$('.arstiem-block').html(data.html);
				}
			},
			fail: function (error) {
				console.log(error);
			}
		});
	},

	// if nothing passed we init reservationProgress with default values
	// or set passed object otherwise
	setReservationInProgress: function (object) {
		object = object || null;

		if(!object) {
			object = {
				inProgress: false,
				isPaid: false,
				isCountryPaid: false,
				price: null,
				serviceId: null,
				serviceName: '',
				serviceDescription: null
			};
		}

		profile.reservationProgress = object;

		// If selected service has the  description
		$('.service-info-link').toggle(profile.reservationProgress.serviceDescription !== null);
	},

	handleOrderPopup: function() {

		//
		// PROMO block handler

		let $promoBlock = $('.promoCodeBlock')

		if($promoBlock.length) {

			let $promoInput = $promoBlock.find('#promo'),
				$promoBtn = $promoBlock.find('.promo-btn'),
				$promoFieldGroup = $promoBlock.find('.promoFieldGroup'),
				$promoSuccess = $promoBlock.find('.promoSuccess'),
				$errorBlock = $promoBlock.find('.promoFieldError'),
				$reservationBtn = $('.order-details .reservation-btn'),
				$payBtn = $('.order-details .pay-btn'),
				$orderAgree = $('.order-details .order-agree')

			//
			console.log('PROMO available')

			$promoInput.off('input').on('input', function (e) {

				$promoBtn.toggleClass('disabled', $promoInput.val().length < 1)
				$errorBlock.hide()
			})

			$promoBtn.off('click').on('click', function (e) {

				e.preventDefault()

				if($(this).hasClass('disabled')) {
					return false
				}

				let promoCode = $promoInput.val()

				console.log('Checking promo: ' + promoCode)

				let reqData = {
					promoCode: promoCode,
					clinicId: $('#clinic_id').val(),
					serviceId: $('#serviceId').val(),
					reservationId: $('#reservation_id').val(),
					lockId: $('#lock_id').val(),
					orderId: $('#orderId').val(),
				}

				ajaxRequest('/profile/checkPromoCode/', reqData, function(data) {

					if(data.logged_off) {
						location.reload()
					}

					if(data.success) {

						let $itemPrice = $('.order-details .item-price'),
							$itemTotal = $('.order-details .item-total'),
							$orderTotal = $('.order-details .order-total-value'),
							price = data.newPrice + ' ' + '€'


						$itemPrice.html(price)
						$itemTotal.html(price)
						$orderTotal.html(price)

						$promoFieldGroup.hide()
						$promoSuccess.show()

						if(data.newPrice === '0.00') {
							$orderAgree.hide()
							$payBtn.hide()
							$reservationBtn.show()

							$reservationBtn.off('click').on('click', function(e) {
								e.preventDefault()
								profile.freeReservation()
							})
						}

					} else {

						$errorBlock.show()
					}


				})
			})
		}

		// if we in orderDetailsPopup
		var $orderAgree = $('#order-agree'),
			$paymentMethod = $('.payment-method');

		if ($orderAgree.length && $paymentMethod.length) {
			$orderAgree.on('change', function () {
				if ($orderAgree.is(':checked')) {
					$paymentMethod.slideDown(300, position_popup);
					if($('[name="method"]:checked').length) {
						$('.pay-btn').removeClass('disabled');
					}
				} else {
					$paymentMethod.slideUp(300, position_popup);
					$('.pay-btn').addClass('disabled');
				}
			});

			$('[name="method"]').on('change', function (e) {
				if($('[name="method"]:checked').length) {
					if($orderAgree.is(':checked')) {
						$('.pay-btn').removeClass('disabled');
					} else {
						$('.pay-btn').addClass('disabled');
					}
				}
			});
		}


	},

	freeReservation: function () {

		console.log('free reservation for resId')

		let reqData = {
			reservationId: $('#reservation_id').val(),
		}

		ajaxRequest('/profile/finishFreeReservation/', reqData, function(data) {

			console.log('finishFreeReservation')
			console.log(data)

			if (data.logged_off) {
				location.reload()
			}

			if (data.html) {
				$('.popup_bg, .popup').remove();
				$('html').removeClass('popup_open');

				if(data.createVroom && data.createVroom.result) {

					try {
						console.log(JSON.parse(data.createVroom.result));
					} catch (e) {
						var newWindow = window.open();
						newWindow.document.write(data.createVroom.result);
					}
				}

				$('body').append(data.html);
				position_popup();
				cselect();
				tip();

				if(data.slots) {

					$.each(data.slots, function( index, value ) {
						if(window.site.ShowOnlyFreeSlots) {
							$(".line[data-id="+value+"]").addClass('removed_slot');
						} else {
							$(".line[data-id="+value+"]").removeClass("shedule green blue black");
						}
					});
				}

				profile.handleOrderPopup();

				$('.popup .close, .popup_bg').off('click').on('click', function() {
					var $popup = $('.popup');
					if($popup.hasClass('popup-add-reservation') || $popup.hasClass('order-details-popup-parent')) {
						profile.cancelAddReservation();
					} else {
						profile.closePopup();
						//profile.filterReservationCalendar(null, null, false);
					}
				});
			}

		})
	},

	showSpinner: function (msg, type) {

		msg = msg || '';

		var $spinnerContainer = $('.spinner-container');

		if (type === 'calendar') {
			$spinnerContainer = $('.spinner-container-calendar');
		}
                else{
                    type = 'popup';
                }

		$spinnerContainer.find('.spinner-msg').text(msg);
		$spinnerContainer.addClass('active');
	},

	hideSpinner: function (type) {
		var $spinnerContainer = $('.spinner-container');

		if (type === 'calendar') {
			$spinnerContainer = $('.spinner-container-calendar');
		}
                else{
                    type = 'popup';
                }

		$spinnerContainer.removeClass('active');
		$spinnerContainer.find('.spinner-msg').text('');
	},

	addConsultationPopup: function (docData) {

		if(!docData || !docData.doctorId || !docData.clinicId) {
			return false;
		}

		profile.sessionPing();

		sendData = {};
		sendData.doctorId = docData.doctorId;
		sendData.clinicId = docData.clinicId;

		// if we open popup by returning back from order details
		// existing consultation
		if(docData.consultationId) {
			sendData.consultationId = docData.consultationId;
		}

		ajaxRequest('/profile/addConsultationPopup/', sendData, function(data) {

			if (data.location) {
				window.location.href = data.location;
				return;
			}

			if (data.html) {

				profile.showPopup(data.html);

				var serviceSelected = function (opt) {

					var sId = opt.attr('id'),
						isPaid = opt.hasClass('paid'),
						price = opt.data('price') ? opt.data('price') : null,
						descrId = opt.data('description') ? opt.data('description') : null,
						$haveIncuranceChkBox = $('.haveInsuranceBlock');

					console.log('Cons serv selected');
					console.log(sId);
					console.log(isPaid);
					console.log(price);
					console.log(descrId);

					profile.setReservationInProgress({
							inProgress: true,
							isPaid: isPaid,
							isCountryPaid: false,
							price: price,
							serviceId: sId,
							serviceName: opt.text(),
							serviceDescription: descrId
						});


					// TODO: show/hide message, description link, gimenes arsts checkbox
					// // //
					// //
					//


				};

				var $serviceSelect = $('#service_id'),
					isServiceSelected = !!$serviceSelect.val();

				// Using select plugin Selectric
				$serviceSelect.selectric({
					arrowButtonMarkup: '<b class="button">&#x25b2;</b>'
				});

				if(!isServiceSelected) {

					$serviceSelect.off('change').on('change', function (e) {

						if(!$(this).val()) {

							// no service selected
							profile.setReservationInProgress();
							return false;
						}

						var $this = $(this),
							opt = $this.find('option:selected');

						serviceSelected(opt);
					});

				} else {

					serviceSelected($serviceSelect.find('option:selected'));
				}

				// checkbox handler
				var $chkBox = $('#my-doctor-confirm');

				if($chkBox.length) {

					var $btns = $('.popup-add-consultation .btn_cont'),
						$priceBlock = $('.consultation-price-block');

					$chkBox.on('change', function(e) {

						var _this = this;

						// we have users with old IE, so...
						setTimeout(function () {

							if(_this.checked) {
								$priceBlock.hide();
								$btns.html(profile.consultationAddButtons.free);
							} else {
								$priceBlock.show();
								$btns.html(profile.consultationAddButtons.paid);
							}

							position_popup();

						}, 50);

					});
				}

				// add consultation handler
				$(document)
					.off('click', '.add_consultation, .consultation_continue')
					.on('click', '.add_consultation, .consultation_continue', function (e) {

					e.preventDefault();

					var consData = {};
					consData.clinicId = $('.popup-add-consultation #clinic_id').val();
					consData.doctorId = $('.popup-add-consultation #doctor_id').val();
					consData.isGimenesArsts = $('.popup-add-consultation #is_gimenes_arsts').val();
					consData.price = $('.popup-add-consultation #price').val();
					consData.sudzibas = $('.popup-add-consultation #sudzibas').val();
					consData.myDoctorConfirm = $('.popup-add-consultation #my-doctor-confirm').is(':checked') ? 1 : 0;

					profile.consultationAdd(consData);
				});


				// cancel handler
				$(document)
					.off('click', '.popup-add-consultation .cancel a, .popup-add-consultation .close, .popup_bg')
					.on('click', '.popup-add-consultation .cancel a, .popup-add-consultation .close, .popup_bg', function(e) {

					e.preventDefault();

					var consId = $('#consultation_id').val();

					profile.closePopup();

					if(consId) {
						profile.cancelAddConsultation(consId);
					}
				});
			}
		});
	},

	consultationAdd: function (consData) {

		profile.removeErrors();

		ajaxRequest('/profile/addConsultation/', consData, function(data) {

			// debug: result data
			console.log('consultationAdd:');
			console.log(data);

			if (data.location) {
				window.location.href = data.location;
				return;
			}

			if (data.errors) {
				profile.setErrors(data.errors);
				return false;
			}

			if (data.html) {
				$('.popup_bg,.popup').remove();
				$('html').removeClass('popup_open');

				$('body').append(data.html);
				position_popup();
				//cselect();
				tip();

				profile.handleOrderPopup();

				$('.popup .cancel a, .popup .close, .popup_bg').off('click').on('click', function() {
					var $popup = $('.popup');

					if($popup.hasClass('popup-add-consultation') || $popup.hasClass('order-details-popup-parent')) {

						var consId = $('#consultation_id').val();

						if(consId) {
							profile.cancelAddConsultation(consId);
						}
					}

					profile.closePopup();
					location.reload();
				});
			}


		});
	},

	cancelAddConsultation: function (id) {

		console.log('cancelAddConsultation');

		if(!id) {
			console.log('no id');
			return false;
		}

		sendData = {};
		sendData['consultationId'] = id;

		ajaxRequest('/profile/cancelAddConsultation/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			if(data.backUrl) {
				location.href = data.backUrl;
			}

			if(data.canceled) {
				//
				console.log('consultation canceled');
			}
		});


	},

	initRes: function (shedId, serviceId = null, dc = false) {

		if(window.userLoggedIn) {

			profile.addReservationPopup(shedId, false, serviceId, dc);

		} else {

			var cData = profile.collectCalendarData();
			cData = Base64.encode(JSON.stringify(cData));

			window.location.href = 	window.site.loginUrl +
				'?url=' + encodeURIComponent(window.location.href) +
				'&schedule_id=' + shedId +
				'&cdata=' + cData;
		}
	},

	initConsultationLink: function ($consData) {

		if(window.userLoggedIn) {

			profile.addReservationPopup($consData, true);

		} else {

			var cData = profile.collectCalendarData();
			cData = Base64.encode(JSON.stringify(cData));

			window.location.href = 	window.site.loginUrl +
				'?url=' + encodeURIComponent(window.location.href) +
				'&cons_doctor_id=' + $consData.doctorId +
				'&cons_clinic_id=' + $consData.clinicId +
				'&cdata=' + cData;
		}
	},

	initConsultationActionLink: function (el) {

		var $el = $(el),
			resId = $el.data('id'),
			action = $el.data('action');

		if($el.hasClass('disabled') || !resId || !action) {
			return false;
		}

		sendData = {};
		sendData['action'] = action;
		sendData['resId'] = resId;

		ajaxRequest('/profile/' + action + '/', sendData, function (data) {

			console.log('initConsultationActionLink response handler');
			console.log(data);

			if(data.sessionSendResult.result) {

				try {
					var parsedSessResult = JSON.parse(data.sessionSendResult.result);
				} catch (e) {
					var newWindow = window.open();
					newWindow.document.write(data.sessionSendResult.result);
				}
			}

			if(parsedSessResult) {
				console.log(parsedSessResult);
			}

			if(data.location) {
				var win = window.open(data.location, '_blank');
				win.focus();
			}
		});

	},

	openInvoicePdf: function (ordId) {

		if (!ordId) {
			return false;
		}

		console.log('Ord clicked!');
		console.log(ordId);

		$.ajax({

			type: "POST",
			url: '/profile/setRequestedOrder/',
			dataType: 'json',
			data: {
				orderId: ordId,
			}

		}).done(function (data) {

			if (data.logged_off) {
				location.reload();
			}

			if (data.result) {
				window.open('/profile/invoice_pdf/?orderId=' + ordId, '_blank')
			} else {
				console.error('Error setting ordId');
				return false;
			}

		}).fail(function (err) {

			console.error('Request error occurred!');
			console.error(err);
		});

		return false;
	}

};

// Events

$(document).ready(function () {

	// Arstiem add doctor submit
	var $arstiemForm = $('#arstiemForm');
	if($arstiemForm.length) {

		$arstiemForm.find('#submit').on('click', function (e) {
			e.preventDefault();
			profile.removeErrors();
			profile.arstiemAddDoctor();
		});
	}

	// Piesaki Arstu add doctor submit
	var $piesakiArstuForm = $('#piesakiArstuForm');
	if($piesakiArstuForm.length) {

		$piesakiArstuForm.find('#submit').on('click', function (e) {
			e.preventDefault();
			profile.removeErrors();
			profile.piesakiArstuAddDoctor();
		});
	}

	// init Reservation

	$(document).on( "click", '.slide .line', function(e) {
		e.preventDefault();

		if ($(this).hasClass('shedule')) {
			profile.initRes($(this).attr('data-id'));
		}
	});

	profile.setReservationInProgress();

	if(window.scheduleId) {
		profile.initRes(window.scheduleId);
	}


	// Agree terms
	if($('.agree_terms_block').length > 0) {

		var $checkBoxes = $('#agreement, #privacy_policy'),
			$continueBtn = $('#continue-btn');

		// checkboxes
		$checkBoxes.on('change', function () {

			profile.removeErrors();

			if($('#agreement').is(':checked') && $('#privacy_policy').is(':checked')) {
				$continueBtn.removeClass('disabled');
			} else {
				$continueBtn.addClass('disabled');
			}
		});

		$continueBtn.on('click', function (e) {
			e.preventDefault();
			if(!$(this).hasClass('disabled')) {
				profile.agreeTerms();
			}
		});
	}

	// Remove error from changed form element
	$(document).on('change', 'input[type="checkbox"], input[type="radio"], select', function (e) {

		var $wrongEl = $(e.target).parents('.wrong');

		if($wrongEl.length > 0) {
			profile.removeErrors($wrongEl.eq(0));
		}
	});

	$(document).on('input', ':text', function (e) {

		var $wrongEl = $(e.target).parents('.cinput');

		if($wrongEl.length > 0) {
			profile.removeErrors($wrongEl.eq(0));
		}
	});

	$(document).on('input keyup click', 'textarea', function (e) {

		var $wrongEl = $(e.target).parents('.cinput');

		if($wrongEl.length > 0) {
			profile.removeErrors($wrongEl.eq(0));
		}
	});

	/**
	 * Add reservation popup events
	 */

	// service select handler
	$(document).on('change', '#service_id', function (e) {

		var $servicesOpts = $('#service_id option');
		var selectedServiceId = $(this).val();
		var selectedService = selectedServiceId ? $servicesOpts.filter('option[value="' + $(this).val() + '"]').data('service-title').trim() : null;

		if(selectedServiceId) {


			if($servicesOpts.filter('option[value="' + $(this).val() + '"]').data('price')) {
				profile.selectedServicePrice = $servicesOpts.filter('option[value="' + $(this).val() + '"]').data('price');
			} else {
				profile.selectedServicePrice = null;
			}

			if($servicesOpts.filter('option[value="' + $(this).val() + '"]').data('origprice')) {
				profile.selectedServiceOrigPrice = $servicesOpts.filter('option[value="' + $(this).val() + '"]').data('origprice');
			} else {
				profile.selectedServiceOrigPrice = null;
			}

		} else {
			profile.selectedServicePrice = null;
			profile.selectedServiceOrigPrice = null;
		}

		profile.selectedServiceId = selectedServiceId;

		var consSlotPrice = null;

		var paymentsEnabled = $('#paym_enabled').val() === '1',
			clinicId = $('#clinic_id').val(),
			doctorId = $('#doctor_id').val();

		var $this = $(this),
			$buttonBlock = $('.reservation-popup-content #service_select_brn_cont'),
			isConsultation = $('#is_consultation').val() === '1';

		var elCont = $('.timeSelectContainer'),
			el = elCont.find('.timeSelectWidget');

		if(isConsultation) {

			var isAnyTimeChecked = $('#anyTimeChecked').val(),
				selectedTime = $('#selected_time').val(),
				ts = null;

			if(el.length) {
				elCont.removeClass('hidden');
				el = el[0];
				ts = new TimeSelect(el, clinicId, doctorId, null, selectedService, selectedServiceId);
				//ts = new TimeSelect(el, clinicId, doctorId, null, services);
				profile.ts = Object.keys(ts).length ? ts : null;

				if(ts) {

					ts.setOptions({
						title: messages.calendarMessages.selectTime,
						showEmptyDates: true,
						times: 5,
					});

					//ts.init();

					var popup = $('.popup-add-reservation'),
						$timeCont = popup.find('.selected-time'),
						$anyTimeCheckbox = popup.find('#anyTime');

					if(selectedTime && selectedTime !== '*') {
						ts.select(selectedTime);
					}

					$timeCont.find('.message').html(messages.calendarMessages.timeNotSelected).removeClass('hidden');
					$timeCont.find('.datetime').addClass('hidden');

					if(ts) {

						$(el).off('ts.select').on('ts.select', function (e, data) {

							if(data.price) {
								consSlotPrice = data.price;
								$('.service-price').text(consSlotPrice);
							} else {
								consSlotPrice = null;
								$('.service-price').text(profile.reservationProgress.price ? profile.reservationProgress.price : '0.00');
							}

							var $timeCont = popup.find('.selected-time');
							$timeCont.find('.time').html(data.selectedTime.time.string);
							$timeCont.find('.date').html(data.selectedTime.date);
							$timeCont.find('.month').html(data.selectedTime.monthStr);
							$timeCont.find('.message').addClass('hidden');
							$timeCont.find('.datetime').removeClass('hidden');
							popup.find('#selected_time').val(data.selectedTime.string + ' ' + data.selectedTime.time.string);
							popup.find('#schedule_id').val(data.selectedTime.scheduleId);
							$anyTimeCheckbox[0].checked = false;
							$anyTimeCheckbox.parents('.item').removeClass('checked');
							$buttonBlock.removeClass('disabled-btn');
							$buttonBlock.find('.btng').removeClass('disabled-btn');
						});

						$(el).off('ts.unselect').on('ts.unselect', function (e) {

							consSlotPrice = null;
							$('.service-price').text(profile.reservationProgress.price ? profile.reservationProgress.price : '0.00');

							var $timeCont = popup.find('.selected-time');
							$timeCont.find('.message').html(messages.calendarMessages.timeNotSelected).removeClass('hidden');
							$timeCont.find('.datetime').addClass('hidden');
							popup.find('#selected_time').val('');
							popup.find('#schedule_id').val('');
							$buttonBlock.addClass('disabled-btn');
							$buttonBlock.find('.btng').addClass('disabled-btn');
						});

						$('#anyTime').off('change').on('change', function (e) {

							var $this = $(this),
								$timeCont = popup.find('.selected-time'),
								$selTimeInput = popup.find('#selected_time');

							popup.find('#schedule_id').val('');

							setTimeout(function () {

								if($this.is(':checked')) {
									$timeCont.find('.message').html(messages.calendarMessages.slectedAnyTime).removeClass('hidden');
									$timeCont.find('.datetime').addClass('hidden');
									ts.unselect();
									$selTimeInput.val('*');
									$buttonBlock.removeClass('disabled-btn');
									$buttonBlock.find('.btng').removeClass('disabled-btn');

								} else {
									$timeCont.find('.message').html(messages.calendarMessages.timeNotSelected).removeClass('hidden');
									$timeCont.find('.datetime').addClass('hidden');
									$selTimeInput.val('');
									$buttonBlock.addClass('disabled-btn');
									$buttonBlock.find('.btng').addClass('disabled-btn');
								}
							});
						});

						if(isAnyTimeChecked) {

							setTimeout(function () {

								var $anyTime = $('#anyTime');

								$anyTime.val('1').prop('checked', true).parents('label').addClass('checked');
								$anyTime.trigger('change');

							}, 100);
						}
					}
				}
			}
		}

		$buttonBlock.html('');

		var popupFixPosition = function () {

			setTimeout(function () {

				// if popup height more than window height, we align it to the top
				var $popup = $('.popup-add-reservation');

				position_popup( $popup[0].clientHeight > window.innerHeight );

			}, 600);
		};

		var changeClasses = function (type, serviceId) {

			serviceId = serviceId || null;

			var $serviceWarningsContainer = $('.service-warnings-container'),
				$serviceRemoteContainer = $('.service-remote-container');

			var $haveIncuranceChkBox = $('.haveInsuranceBlock');

			// var chkBox = '<hr><div class="checkbox_field"><label class="item blue">' +
			// 	'            <input type="checkbox" class="" id="country_agreement" value="1">' +
			// 	'            <span class="box"><i class="fa fa-check"></i></span>' +
			// 	'            Piekrītu <a href="/noteikumi-par-valsts-apmaksato-pakalpojumu/" target="_blank">noteikumiem par valsts apmaksāto pakalpojumu sniegšanu</a>' +
			// 	'        </label></div>';

			var chkBox = decodeURIComponent(window.popupWarnings.defaultCheckbox.replace(/\+/g, ' '));

			// buttons and containers

			switch ( type ) {
				case 'unselected':
					$serviceWarningsContainer.html(decodeURIComponent(window.popupWarnings.select.replace(/\+/g, ' ')));
					$buttonBlock.html(window.popupBtns.btnRes + window.popupBtns.btnCancel);
					chkBox = '';
					$haveIncuranceChkBox.css({display: 'none'});
					break;

				case 'prePaid':
					$serviceWarningsContainer.html(decodeURIComponent(window.popupWarnings.prePaid.replace(/\+/g, ' ')));
					$buttonBlock.html(window.popupBtns.btnCont + window.popupBtns.btnCancel);
					chkBox = '';
					$haveIncuranceChkBox.css({display: 'block'});
					break;

				case 'paid':
					$serviceWarningsContainer.html(decodeURIComponent(window.popupWarnings.paid.replace(/\+/g, ' ')));
					$buttonBlock.html(window.popupBtns.btnRes + window.popupBtns.btnCancel);
					$haveIncuranceChkBox.css({display: 'none'});
					break;

				case 'gov':
					$serviceWarningsContainer.html(decodeURIComponent(window.popupWarnings.free.replace(/\+/g, ' ')));
					$buttonBlock.html(window.popupBtns.btnRes + window.popupBtns.btnCancel);
					$buttonBlock.find('.reservation-btn').removeClass('disabled-btn');
					$haveIncuranceChkBox.css({display: 'none'});
					break;

				case 'both':
					$serviceWarningsContainer.html(decodeURIComponent(window.popupWarnings.both.replace(/\+/g, ' ')));
					$buttonBlock.html(window.popupBtns.btnRes + window.popupBtns.btnCancel);
					$buttonBlock.find('.reservation-btn').removeClass('disabled-btn');
					$haveIncuranceChkBox.css({display: 'none'});
					break;
			}

			if(isConsultation || type === 'unselected') {
				$buttonBlock.find('.btng').addClass('disabled-btn');
			} else {
				$buttonBlock.find('.btng').removeClass('disabled-btn');
			}

			// if we have special warning for selected service we show it
			if(serviceId && typeof window.popupWarnings[serviceId] !== 'undefined' && window.popupWarnings[serviceId]) {

				setTimeout(function () {

					var personSelected = $('.popup_attendee_select input[name=popup_attendee]:checked').val() === 'other';
					var warningKey = serviceId;

					if(personSelected && window.popupWarnings[serviceId + '_original']) {
						warningKey = serviceId + '_original';
					}

					var message = "<div class='info warning'>" + decodeURIComponent(window.popupWarnings[warningKey].replace(/\+/g, ' ')) + "</div>";
					$serviceWarningsContainer.html(message + chkBox);
					checkboxes();

				}, 100);
			}

			$serviceRemoteContainer
				.toggle(
					serviceId &&
					typeof window.popupWarnings.remote[serviceId] !== 'undefined' &&
					window.popupWarnings.remote[serviceId]
				);

			// Change textarea piezimes label: piezimes or sudzibas
			var noticeLabel = $(".reservation-popup-content label[for='notice']");
			if (serviceId && typeof window.popupWarnings.remote[serviceId] !== 'undefined' &&
				window.popupWarnings.remote[serviceId]) {
				noticeLabel.text(noticeLabel.data('val-complaints'));
			} else {
				noticeLabel.text(noticeLabel.data('val-notes'));
			}

			position_popup();
		};

		if(!$this.val()) {
			profile.setReservationInProgress();
			changeClasses('unselected');

			if(isConsultation) {
				popup.find('.consultation-time-block').css('max-height', '0');
			}

			popupFixPosition();

		} else {

			var $selectedOption = $(this).find('option[value="'+ $(this).val() +'"]');

			profile.setReservationInProgress({
				inProgress: true,
				isPaid: $selectedOption.hasClass('paid'),
				isCountryPaid: $('.reservation-popup-content').data('payment-type'),
				price: $selectedOption.data('price') ? $selectedOption.data('price') : null,
				serviceId: $(this).val(),
				serviceName: $selectedOption.text(),
				serviceDescription: $selectedOption.data('description') ? $selectedOption.data('description') : null
			});

			if(profile.reservationProgress.isPaid && profile.reservationProgress.price && paymentsEnabled) {
				// Paid service selected
				changeClasses('prePaid', $this.val());
				var personSelected = $('.popup_attendee_select input[name=popup_attendee]:checked').val() === 'other';

				var currPrice = personSelected ? profile.selectedServiceOrigPrice : profile.selectedServicePrice;
				$('.service-price').text(currPrice);
			} else if(profile.reservationProgress.isCountryPaid === 2) {
				// Country-paid service selected
				changeClasses('paid', $this.val());
			} else if(profile.reservationProgress.isCountryPaid === 1) {
				// Country-paid service selected
				changeClasses('gov', $this.val());
			} else if(profile.reservationProgress.isCountryPaid === 0) {
				// Country-paid service selected
				changeClasses('both', $this.val());
			} else {
				// Nothing there in payment type, this is consultation probably
				changeClasses('both', $this.val());
			}

			if(isConsultation) {
				popup.find('.consultation-time-block').css('max-height', '500px');
			}

			popupFixPosition();
		}
	});

	// cancelReservationPopup reason select handler
	$(document).on('change', '#reason', function (e) {

		var value = $(this).val(),
			$reasonOther = $('.reason_other'),
			$statusReason = $('#status_reason'),
			otherText = $('#reason option[value="other"]').text().trim();

		$reasonOther.toggle(value === 'other');
		position_popup();

		if(value !== 'other') {
			$statusReason.val($('#reason option[value="' + value + '"]').text().trim());
		} else {
			$statusReason.val('');
		}

		$reasonOther.off('keyup').on('keyup', function (e) {

			if($reasonOther.is(':visible') && value === 'other') {

				if($reasonOther.find('textarea').val().trim()) {
					$statusReason.val(otherText + ': ' + $reasonOther.find('textarea').val());
				} else {
					$statusReason.val('');
				}
			}
		});
	});

	// cancelReservationPopup reason select handler
	$(document).on('change', '#refund', function (e) {

		var $refund = $('.refund');
		var paymentMethod = $('#payment_method').val();

		if(
			paymentMethod === 'cards' ||
			paymentMethod === 'insurance' ||
			paymentMethod === 'dccard'
		) {
			return false;
		}

		$refund.toggle($(this).is(':checked'));
		position_popup();

		if($refund.is(':visible')) {

			$refund.find('input').off('keyup').on('keyup', function (e) {

				var val = $(this).val(),
					$warningMsg = $('.warning_msg');

				if(val.length > 0) {

					$warningMsg.show();

					if(validateIBAN(val)) {
						$warningMsg.text(profile.IBAN.ok).addClass('ok');
					} else {
						$warningMsg.text(profile.IBAN.warning).removeClass('ok');
					}
				} else {
					$warningMsg.hide();
				}
			});
		}
	});

	// Reservation button handler
	$(document).on('click', '.reservation-popup-content .btng', function (e) {
		e.preventDefault();

		var $this = $(this);

		if($this.hasClass('disabled-btn')) {
			return false;
		}

		profile.addReservation($('.reservation-popup-content').data('slot'));
	});

	// Reservation popup cancel button handler
	$(document).on('click', '.reservation-popup-content .btn_cont .cancel a', function (e) {
		e.preventDefault();
		profile.cancelAddReservation();
	});


	// service info link handler
	$(document).on('click', '.service-info-link a', function (e) {

		profile.sessionPing();

		var $popupServiceInfo = $('.popup-service-info');

		$popupServiceInfo.addClass('active');
		$('.popup-service-info-title').text(profile.reservationProgress.serviceName);
		profile.scrollPosition = $(window).scrollTop();
		$("body").css("overflow", "hidden");

		$('.popup-service-info .close').off('click').on('click', function () {
			$popupServiceInfo.removeClass('active');
			$("body").css("overflow", "auto");
			$(window).scrollTop(profile.scrollPosition);
			profile.scrollPosition = null;
			$popupServiceInfo.find('.content-loaded').html('');
		});

		var descId = profile.reservationProgress.serviceDescription,
			$content = $('.popup-service-info-content');

		profile.showSpinner(messages.wait_loadingServiceDescription);

		sendData = {};
		sendData['descriptionId'] = descId;

		ajaxRequest('/profile/getServiceDescription/', sendData, function(data) {

			if(data.logged_off) {
				location.reload();
			}

			$content.find('.content-loaded').html(data.html);

			profile.hideSpinner();
		});
	});


	/**
	 * Second step -- Order details
	 */

	// Back button handler
	$(document).on('click', '.order-details.popup-step-2 .back-btn', function (e) {
		e.preventDefault();

		if($('#reservation_id').val()) {

			profile.backStepOneReservation();

		} else if ($('#consultation_id').val()) {

			profile.backStepOneConsultation();
		}
	});

	// Pay button handler
	$(document).on('click', '.order-details.popup-step-2 .pay-btn', function (e) {
		e.preventDefault();

		if($(this).hasClass('disabled')) {
			return false;
		}

		profile.performPayment();
	});

	// Cancel button handler
	$(document).on('click', '.order-details.popup-step-2 .cancel-btn', function (e) {
		e.preventDefault();
		profile.cancelAddReservation();
	});

	// Book consultation button handler
	$(document).on('click', '.btn-consultation', function () {

		var _data = $(this).parents('.dclist ').data();

		profile.initConsultationLink(_data);
	});

	if(getParameterByName('orderId')) {

		profile.sessionPing();

		var sendData = {};
		sendData['orderId'] = getParameterByName('orderId');

		ajaxRequest('/profile/showOrderDetailsPopup/', sendData, function(data) {

			if(data.logged_off) {
                location.href = window.location.href.split('?')[0];
			}

			if(data.finalStatus) {
				console.log('Final status: ' + data.finalStatus);
				var newURL = location.href.split("?")[0];
				window.history.pushState('object', document.title, newURL);
				return false;
			}

			if(data.location) {
				window.location.href = data.location;
				return true;
			}

			if(data.error) {
				console.log(data.error);
			}

			if(data.html) {

			    // remove query params
                var uri = window.location.toString();
                if (uri.indexOf("?") > 0) {
                    // var clean_uri = uri.substring(0, uri.indexOf("?"));
					var clean_uri = removeParam('orderId', uri);
                    window.history.replaceState({}, document.title, clean_uri);
                }

				// show popup

				$('body').append(data.html);
				position_popup();
				cselect();
				tip();

				data.slots = data.slots.split(',');

				$.each(data.slots, function( index, value ) {
					if(window.site.ShowOnlyFreeSlots) {
						$(".line[data-id="+value+"]").addClass('removed_slot');
					} else {
						$(".line[data-id="+value+"]").removeClass("shedule green blue black");
					}
				});

				profile.handleOrderPopup();

				$('.popup .close, .popup_bg').off('click').on('click', function() {
					var $popup = $('.popup');
					if($popup.hasClass('popup-add-reservation') || $popup.hasClass('order-details-popup-parent')) {
						profile.cancelAddReservation();
					}
					profile.closePopup();
				});

			}

		});
	}

	if(getParameterByName('openRes')) {

		let resId = getParameterByName('openRes')
		let url = location.href.split('?')[0]
		window.history.replaceState({}, document.title, url);
		profile.openReservation(resId)
	}

	// transaction complete check
	var $payment_success_block = $('.payment_success_block');

	if($payment_success_block.length) {

		profile.sessionPing();

		sendData = {};
		sendData.orderId = $('#orderId').val();

		var transactionInterval = setInterval(function () {

			ajaxRequest('/profile/getTransaction/', sendData, function(data) {

				if(data.status && data.status === 1) {

					clearInterval(transactionInterval);

					$('.transaction-incomlete').hide();

					var $trComplete = $('.transaction-comlete');
					$trComplete.show();

					$trComplete.find('.cont').html(data.html);
				}
			});

		}, 3000);
	}

	if(window.userLoggedIn) {
		profile.clearSessionTimers();
		profile.setSessionTimer();
	}

	if(window.popupMessage) {
		$('body').append(Base64.decode(window.popupMessage));
		position_popup();
	}

	// check if we have restore data for calendar filter
	if(profile.filterCalendar) {
		var cData = profile.filterCalendar;
		profile.filterCalendar = false;
		profile.restoreCalendar(cData);
	}

	// If user is on profile pages and can see Mani izmeklejumi link...
	// We should set interval check of site availability
	//
	var $manidatiLink = $('#manidati_href');

	if($manidatiLink.length) {

		function reqMd() {

			$.ajax({

				type: "POST",
				url: '/profile/isManiDatiAvailable/',
				dataType: 'json',
				data: {}

			}).done(function (data) {

				if(data.logged_off) {
					location.reload();
				}

				if(data.success) {
					window.location.replace(data.url + '?' + sess);
				} else {
					console.error('Mani izmeklejumi is temporary unavailable.');
					return false;
				}

			}).fail(function (err) {

				console.error('Request error occurred!');
				console.error(err);
			});
		}

		$manidatiLink.off('click').on('click', function (e) {

			e.preventDefault();

			if($(this).hasClass('disabled')) {
				return false;
			}

			reqMd();
		});

		function checkUrl() {

			var img = $('<img src="/md_available" />');

			img.off('load').on('load', function () {
				$manidatiLink.removeClass('disabled');
			});

			img.off('error').on('error', function () {
				$manidatiLink.addClass('disabled');
			});
		}

		setInterval(checkUrl, 3000);
		checkUrl();


		console.log('Handlers set');

		$(document).on('click', '.printOrdBtn', function (e) {


		});
	}

	var changeServiceMessage = function (serviceId) {

		setTimeout(function () {

			if(!serviceId) {
				return false;
			}

			var person = $('.popup_attendee_select input[name=popup_attendee]:checked').val() === 'other',
				chkBox = decodeURIComponent(window.popupWarnings.defaultCheckbox.replace(/\+/g, ' ')),
				$serviceWarningsContainer = $('.service-warnings-container');

			if(typeof window.popupWarnings[serviceId] !== 'undefined' && window.popupWarnings[serviceId]) {

				var warningKey = serviceId;

				if(person && window.popupWarnings[serviceId + '_original']) {
					warningKey = serviceId + '_original';
				}

				var message = "<div class='info warning'>" + decodeURIComponent(window.popupWarnings[warningKey].replace(/\+/g, ' ')) + "</div>";
				$serviceWarningsContainer.html(message + chkBox);
				checkboxes();

			} else {

				var $priceSpan = $('.info.warning .service-price');

				$priceSpan.text(person ? profile.selectedServiceOrigPrice : profile.selectedServicePrice);
			}

		}, 100);
	};

	$(window).on('attendeeChange', function (e, param) {

		if(profile.selectedServiceId) {

			changeServiceMessage(profile.selectedServiceId)
		}
	});

});
