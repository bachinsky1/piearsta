$(document).ready(function () {

    if(
        $('.authorization_block').length ||
        $('.profil_edit').length
    ) {

        // $("input[autocomplete='off']").attr('readonly', true).on('focus', function () {
        //     $(this).removeAttr('readonly')
        // });

        monkeyPatchAutocomplete();

        var $country = $('#country'),
            countryHandlerSet = false,
            verifiable = verification.isVerifiable === 1;

        function handleCountryChange() {

            $country.off('keyup').on('keyup', function (e) {

                var $this = $(this),
                    allowedCountries = verification.allowedCountries,
                    countryCode = null;

                countryHandlerSet = true;

                // validate country string
                var isValid = /^\D*\(??\)$/.test($this.val());

                if(isValid) {
                    countryCode = $this.val().slice(-3, -1).toUpperCase();
                }

                if(countryCode && allowedCountries.indexOf(countryCode) !== -1) {
                    verifiable = true;
                    $('.verify_link').removeClass('disabled');
                    if(countryCode === 'LV') {
                        $('#isResident').prop('checked', true).trigger('change');
                    }
                    $('.verification_block').show();
                } else {
                    verifiable = false;
                    $('.verify_link').addClass('disabled');
                    $('.verification_block').hide();
                }
            });
        }

        // is resident radio handler

        // init
        if($('.radio_field input[name=resident]:checked').val() === '1'){

            $('.twocol .noresident, .twocol.noresident').hide().removeClass('visible');
            $('.twocol .resident, .twocol.resident').show();
            $country.attr('disabled', true).val('Latvija (LV)');
            $('.country-field .cinput').addClass('disabled');
            $('.verify_link').removeClass('disabled');
            $('.verification_block').show();
            verifiable = true;

        } else {

            $('.twocol .resident, .twocol.resident').hide();
            $('.twocol .noresident, .twocol.noresident').show().addClass('visible');
            $country.attr('disabled', false);
            $('.country-field .cinput').removeClass('disabled');
            $('.verify_link').addClass('disabled');
            handleCountryChange();
            $country.trigger('keyup');
        }

        // change handler
        $('.radio_field input[name=resident]').change(function(){

            if($(this).val() === '1') {

                $('.twocol .noresident, .twocol.noresident').hide().removeClass('visible');
                $('.twocol .resident, .twocol.resident').show();
                $country.attr('disabled', true).val('Latvija (LV)');
                $('.country-field .cinput').addClass('disabled');
                $('.verify_link').removeClass('disabled');
                $('.verification_block').show();
                verifiable = true;

            } else {

                $('.twocol .noresident, .twocol.noresident').show().addClass('visible');
                $('.twocol .resident, .twocol.resident').hide();
                $country.attr('disabled', false).val('');
                $('.country-field .cinput').removeClass('disabled');
                $('.verify_link').addClass('disabled');
                handleCountryChange();
            }
        });


        // country autocomplete
        $(".country-autocomplete").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/profile/countryAutocomplete/",
                    dataType: "json",
                    data: {
                        q: request.term
                    },

                    success: function (data) {

                        $(window).trigger('acresults_change');
                        response(data);
                    }
                });
            },
            select: function(e, ui) {

                var $this = $(this);

                if(!countryHandlerSet) {
                    handleCountryChange();
                }

                $this.data('current', ui.item.value);
                $this.data('acfilled', '1');

                setTimeout(function () {
                    $country.trigger('keyup');
                }, 50);
            },
            autoFocus: true,
            minLength: 2,
            position: {my: "left-12 top+20"},
            appendTo: ".cinput.focused input"
        });



        // Enter key press handlers

        // for registration page
        $('.register').keypress(function (e) {
            if (e.which === 13) {
                profile.profileRegister();
            }
        });

        // for profile data edit page
        $('.profile').keypress(function (e) {
            if (e.which === 13) {
                profile.saveProfile();
            }
        });


        jQuery.ui.autocomplete.prototype._resizeMenu = function () {
            var ul = this.menu.element;
            ul.outerWidth(this.element.outerWidth() + 24);
        }

    }


});
