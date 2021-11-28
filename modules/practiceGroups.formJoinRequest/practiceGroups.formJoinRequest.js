( function () {

    if( typeof mw === 'undefined' || mw === null ) {
        throw "";
    }

    mw.practiceGroups = mw.practiceGroups || {};

    mw.practiceGroups.formJoinRequest = {
        formId: 'form-joinrequest',
        encodeFormData: function() {
            var formData = {};

            formData.practicegroupsuser_id = 0;
            formData.practicegroup_id = $( '#' + mw.practiceGroups.formJoinRequest.formId + '-practicegroupid' ).val();
            formData.user_id = $( '#' + mw.practiceGroups.formJoinRequest.formId + '-userid' ).val();

            var affiliatedEmailSelector = $( '#' + mw.practiceGroups.formJoinRequest.formId + '-affiliatedemail' );
            if( affiliatedEmailSelector.length && affiliatedEmailSelector.val() ) {
                formData.awaiting_email_verification_since = Date.now();
                formData.affiliated_email = affiliatedEmailSelector.val();
            }

            var requestReasonSelector = $( '#' + mw.practiceGroups.formJoinRequest.formId + '-requestreason' );
            if( requestReasonSelector.length ) {
                formData.requested_since = Date.now();
                formData.request_reason = requestReasonSelector.val();
            }

            return formData;
        },
        init: function() {
            $( '#' + mw.practiceGroups.formJoinRequest.formId + '-affiliatedemail' ).on( 'change', function( event ) {
                mw.practiceGroups.formJoinRequest.validateAffiliatedEmail();
            } );

            $( '#' + mw.practiceGroups.formJoinRequest.formId + '-submit' ).click( function() {
                mw.practiceGroups.formJoinRequest.submit();
            } );

            $( '#' + mw.practiceGroups.formJoinRequest.formId ).submit( function( e ) {
                e.preventDefault();

                $( '#' + mw.practiceGroups.formJoinRequest.formId + '-submit' ).trigger( 'click' );
            } );
        },
        submit: function() {
            mw.practiceGroups.formJoinRequest.validateForm( function() {
                mw.practiceGroups.common.confirm(
                    mw.msg( 'practicegroups-form-joinrequest-submit-confirm', $( '#' + mw.practiceGroups.formJoinRequest.formId + '-practicegroupname' ).val() ),
                    function() {
                        var pgaction = 'edituser';

                        var apiParams = {
                            action: 'practicegroups',
                            pgaction: pgaction,
                            useraction: 'joinrequest'
                        };

                        $.extend( apiParams, mw.practiceGroups.formJoinRequest.encodeFormData() );

                        new mw.Api().postWithEditToken( apiParams ).then( function ( result ) {
                            if( result.practicegroups[ pgaction ].status === 'ok' ) {
                                location.href = mw.Title.newFromText( 'Special:PracticeGroups' ).getUrl();
                            } else {
                                console.log(result);
                                mw.practiceGroups.common.showAlert( result.practicegroups[ pgaction ].message, 'danger' );
                            }
                        } ).fail( function(a, b, c) {
                            console.log( b );
                            mw.practiceGroups.common.showAlert( mw.msg( 'practicegroups-error-generic' ), 'danger' );
                        } );
                    }
                );
            } );
        },
        validateAffiliatedEmail: function() {
            var hasAffiliatedDomain = false;

            var emailSelector = $( '#' + mw.practiceGroups.formJoinRequest.formId + '-affiliatedemail' );

            if( !emailSelector.val() ) {
                if( $( '#' + mw.practiceGroups.formJoinRequest.formId + '-requestreason' ).length ) {
                    // Email is optional if user can also join by request
                    emailSelector.removeClass( 'is-invalid' ).removeClass( 'is-valid' );

                    return true;
                }

                mw.practiceGroups.common.updateFieldValidation( emailSelector, false, {
                    error: mw.practiceGroups.common.validationErrorRequiredMissing
                } );
            } else if( !mw.practiceGroups.common.isValidEmail( emailSelector.val() ) ) {
                mw.practiceGroups.common.updateFieldValidation( emailSelector, false, {
                    message: mw.msg( 'practicegroups-error-emailnotvalid' )
                } );
            } else {
                var affiliatedDomains = $( '#form-joinrequest-affiliateddomains' ).val().split(',');
                var emailDomain = emailSelector.val().match(/(.*)@(.*)$/)[ 2 ];

                for( var iAffiliatedDomain in affiliatedDomains ) {
                    if( emailDomain === affiliatedDomains[ iAffiliatedDomain ] ) {
                        hasAffiliatedDomain = true;
                    }
                }

                if( !hasAffiliatedDomain ) {
                    mw.practiceGroups.common.updateFieldValidation( emailSelector, false, {
                        message: mw.msg( 'practicegroups-error-emailnotaffiliated' )
                    } );
                } else {
                    mw.practiceGroups.common.updateFieldValidation( emailSelector, true );
                }
            }

            return hasAffiliatedDomain;
        },
        validateForm: function( isValidCallback ) {
            // Tidy up the form
            $( ':input' ).each( function() {
                $( this ).val( $.trim( $( this ).val( ) ) );
            } );

            if( mw.practiceGroups.formJoinRequest.validateAffiliatedEmail() ) {
                isValidCallback();
            }
        }
    };

    mw.practiceGroups.formJoinRequest.init();

}() );