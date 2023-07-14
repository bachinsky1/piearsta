const tfa = {

    // props

    // methods

    init: function() {

        let $tfaSection = $('.sectionProfileTFA ')

        // set handlers and initial state for profile change password page (profile security page)

        if($tfaSection.length) {

            let $switch = $('.switchControl')

            $switch.on('click', function (e) {

                let $this = $(this)

                if($this.hasClass('on')) {
                    $this.removeClass('on')
                    $tfaSection.removeClass('isOn')
                    $tfaSection.removeClass('isConfigured')
                    tfa.removeCode()
                } else {
                    $this.addClass('on')
                    $tfaSection.addClass('isOn')
                }
            })
        }
    },

    tfaOff: function() {
        let $tfaSection = $('.sectionProfileTFA ')
        let $switch = $('.switchControl')
        $switch.removeClass('on')
        $tfaSection.removeClass('isOn')
        $tfaSection.removeClass('isConfigured')
    },

    removeCode: function() {

        ajaxRequest('/profile/tfaRemoveCode/', {}, function(data) {})
    },

    showConfigurePopup: function(wrongCode = false) {

        sendData = {}

        if(wrongCode) {
            sendData['wrongCode'] = true
        }

        ajaxRequest('/profile/showTfaConfigurePopup/', sendData, function(data) {

            if(data.html_popup && data.html_content) {

                tfa.showPopup(data.html_popup)
                $('.tfaPopup .cont').html(data.html_content)

                position_popup()

                $('.tfaPopup .back-btn, .tfaPopup .close-btn').off().on('click', function (e) {
                    tfa.closePopup()
                })

                $('.tfaPopup .continue-btn').off().on('click', function (e) {
                    e.preventDefault()
                    tfa.showConfigurePopupCodeInput()
                })

                $('.codeShowLink').off('click').on('click', function (e) {
                    e.preventDefault()
                    $('.codeHidden').show()
                    $(this).hide()
                })
            }
        })
    },

    showConfigurePopupCodeInput: function() {

        ajaxRequest('/profile/showTfaConfigurePopupCodeInput/', sendData, function(data) {

            if(data.html_content) {

                $('.tfaPopup .cont').html(data.html_content)

                position_popup()

                $('.tfaPopup .back-btn').off().on('click', function (e) {
                    tfa.closePopup()
                    tfa.showConfigurePopup()
                })

                $('.tfaPopup .continue-btn').off().on('click', function (e) {
                    e.preventDefault()
                    tfa.configureRequest()
                })
            }
        })
    },

    configureRequest: function() {

        sendData = {
            code: document.getElementById('code').value,
        }

        ajaxRequest('/profile/tfaConfigureCheckCode/', sendData, function(data) {

            if(data.html_content && !data.item.wrongCode) {

                $('.tfaPopup .cont').html(data.html_content)

                position_popup()

                $('.tfaPopup .close-btn').off().on('click', function (e) {
                    tfa.closePopup()

                    $('.sectionProfileTFA').addClass('isConfigured')
                })

            } else {

                $('.tfaPopup .cont').html(data.html_content)

                position_popup()

                $('.tfaPopup .back-btn').off().on('click', function (e) {
                    tfa.closePopup()
                    tfa.showConfigurePopup()
                })

                $('.tfaPopup .continue-btn').off().on('click', function (e) {
                    e.preventDefault()
                    tfa.configureRequest()
                })
            }
        })
    },

    showAuthPopup: function() {

        ajaxRequest('/profile/tfaShowAuthPopup/', {}, function(data) {

            if(data.html_popup) {

                tfa.showPopup(data.html_popup)
                $('.tfaPopup .cont').html(data.html_content)

                position_popup()

                $('.tfaPopup .cancel-btn').off().on('click', function (e) {
                    e.preventDefault()
                    tfa.closePopup()
                })

                $('.tfaPopup .continue-btn').off().on('click', function (e) {
                    e.preventDefault()
                    tfa.authRequest()
                })
            }
        })
    },

    authRequest: function() {

        sendData = {
            code: $('#code').val()
        }

        ajaxRequest('/profile/tfaCheckAuth/', sendData, function(data) {

            if(data.item && data.item.logout) {
                tfa.closePopup()
                return false
            }

            if(data.html_content) {

                $('.tfaPopup .cont').html(data.html_content)

                position_popup()

                $('.tfaPopup .cancel-btn').off().on('click', function (e) {
                    e.preventDefault()
                    tfa.closePopup()
                })

                $('.tfaPopup .continue-btn').off().on('click', function (e) {
                    e.preventDefault()
                    tfa.authRequest()
                })

            } else if(data.location) {

                window.location.href = data.location
            }
        })
    },

    showPopup: function(html, handleFunction = null) {

        $('body').append(html)
        $('html').addClass('popup_open')

        position_popup()

        if(handleFunction && typeof handleFunction === 'function') {
            handleFunction()
        }
    },

    closePopup: function() {

        // do we need to reset configuration attemts??

        $('.popup .close, .popup_bg').off('click');
        $('.popup_bg, .popup').remove();
        $('html').removeClass('popup_open');
        $(window).trigger('popup_close');
    },
}

$(document).on('ready', function () {
    tfa.init()
})

