( function () {

    if( typeof mw === 'undefined' || mw === null ) {
        throw "";
    }

    mw.practiceGroups = mw.practiceGroups || {};

    mw.practiceGroups.common = {
        validationErrorFailedValidation: 'failedValidation',
        validationErrorNotUnique: 'notUnique',
        validationErrorRequiredMissing: 'requiredMissing',
        addHandlers: function() {
            var elementBase = 'practicegroup';

            var editUserButtonActions = [
                'acceptinvitation',
                'approverequest',
                'demoteadmin',
                'join',
                'promoteadmin'
            ];

            for( var iEditButtonAction in editUserButtonActions ) {
                var editButtonAction = editUserButtonActions[ iEditButtonAction ];

                $( '.' + elementBase + '-' + editButtonAction + '-button' ).click( function() {
                    var buttonParams = $( this ).attr( 'id' ).split( '-' );
                    var buttonAction = buttonParams[ 1 ];
                    var buttonId = buttonParams[ 3 ];
                    var editName;

                    if( buttonAction === 'acceptinvitation' ) {
                        editName = $( '#' + elementBase + '-' + buttonAction + '-button-' + buttonId ).parent().parent().parent().children().first().html().split('<br>')[0];
                    } else if( buttonAction === 'join' ) {
                        editName = $( '#' + elementBase + '-' + buttonAction + '-button-' + buttonId ).parent().parent().parent().children().first().children().first().children().html();
                    } else {
                        editName = $( '#' + elementBase + '-' + buttonAction + '-button-' + buttonId ).parent().parent().parent().children().first().children().first().html();
                    }

                    mw.practiceGroups.common.confirm(
                        mw.msg( 'practicegroups-' + elementBase + '-' + buttonAction + '-confirm', editName ),
                        mw.practiceGroups.common.membershipButtonHandler, [ this, 'edituser', buttonAction, function( buttonId, result ) {
                            if( result.status === 'ok' ) {
                                location.reload();
                            } else {
                                mw.practiceGroups.common.showAlert( result.message, 'danger' );
                            }
                        } ] );
                } );
            }

            var removeUserButtonActions = [
                'cancelinvitation',
                'cancelrequest',
                'declineinvitation',
                'leave',
                'rejectrequest',
                'removeuser'
            ];

            for( var iRemoveButtonAction in removeUserButtonActions ) {
                var removeButtonAction = removeUserButtonActions[ iRemoveButtonAction ];

                $( '.' + elementBase + '-' + removeButtonAction + '-button' ).click( function() {
                    var buttonParams = $( this ).attr( 'id' ).split( '-' );
                    var buttonAction = buttonParams[ 1 ];
                    var buttonId = buttonParams[ 3 ];
                    var removeName;

                    if( buttonAction === 'cancelrequest' || buttonAction === 'declineinvitation' ) {
                        removeName = $( '#' + elementBase + '-' + buttonAction + '-button-' + buttonId ).parent().parent().parent().children().first().html().split('<br>')[0];
                    } else {
                        removeName = $( '#' + elementBase + '-' + buttonAction + '-button-' + buttonId ).parent().parent().parent().children().first().children().first().html();
                    }

                    mw.practiceGroups.common.confirm(
                        mw.msg( 'practicegroups-' + elementBase + '-' + buttonAction + '-confirm', removeName ),
                        mw.practiceGroups.common.membershipButtonHandler, [ this, 'removeuser', buttonAction, function( buttonId, result ) {
                            if( result.status === 'ok' ) {
                                $( '#' + elementBase + '-' + buttonAction + '-button-' + buttonId ).parent().parent().parent().remove();

                                if( buttonAction === 'cancelinvitation'
                                    || buttonAction === 'cancelrequest'
                                    || buttonAction === 'leave' ) {
                                    location.reload();
                                }
                            } else {
                                mw.practiceGroups.common.showAlert( result.message, 'danger' );
                            }
                        } ]
                    );
                } );
            }

            $( '.' + elementBase + '-inviteuser-button' ).click( function() {
                var buttonParams = $( this ).attr( 'id' ).split( '-' );
                var buttonAction = buttonParams[ 1 ];

                var user = $( '#practicegroups-inviteuser-search' ).val();

                if( !user ) {
                    $( '#practicegroups-inviteuser-search' ).focus();

                    return;
                }

                mw.practiceGroups.common.confirm(
                    mw.msg( 'practicegroups-' + elementBase + '-' + buttonAction + '-confirm', user ),
                    mw.practiceGroups.common.membershipButtonHandler, [ this, 'edituser', 'inviteuser', function( buttonId, result ) {
                        if( result.status === 'ok' ) {
                            location.reload();
                        } else {
                            mw.practiceGroups.common.showAlert( result.message, 'danger' );
                        }
                    } ] );
            } );

            $( '#practicegroups-inviteuser-search' ).on( 'keyup', function( e ) {
                if( e.key === 'Enter' ) {
                    $( '.' + elementBase + '-inviteuser-button' ).trigger( 'click' );
                }
            } );

            $( '.' + elementBase + '-resendemail-button' ).click( function() {
                mw.practiceGroups.common.membershipButtonHandler( this, 'resendverificationemail', 'resendverificationemail', function( buttonId, result ) {
                    if( result.status === 'ok' ) {
                        mw.practiceGroups.common.showAlert( mw.msg( 'practicegroups-email-verification-sent' ), 'success' );
                    } else {
                        mw.practiceGroups.common.showAlert( result.message, 'danger' );
                    }
                } );
            } );
        },
        confirm: function( message, confirmedCallback, callbackParams ) {
            $( '#modalConfirm' ).remove();

            $( '#bodyContent' ).prepend( $( '<div>', {
                    'class': 'modal fade',
                    'id': 'modalConfirm',
                    'tabindex': '-1',
                    'role': 'dialog',
                    'aria-labelledby': 'modalConfirmLabel',
                    'aria-hidden': 'true'
                } ).append( $( '<div>', {
                        'class': 'modal-dialog',
                        'role': 'document'
                    } ).append( $( '<div>', {
                            'class': 'modal-content'
                        } ).append( $( '<div>', {
                                'class': 'modal-header'
                            } ).append( $( '<h5>', {
                                    'class': 'modal-title',
                                    'id': 'modalConfirmLabel'
                                } ).append( mw.msg( 'practicegroups-areyousure' ) )
                            ), $( '<div>', {
                                'class': 'modal-body'
                            } ).append( message ),
                            $( '<div>', {
                                'class': 'modal-footer'
                            } ).append( $( '<button>', {
                                    'type': 'button',
                                    'class': 'btn btn-primary',
                                    'data-dismiss': 'modal',
                                    'id': 'modalConfirmYes'
                                } ).append( mw.msg( 'practicegroups-yes' ) ),
                                $( '<button>', {
                                    'type': 'button',
                                    'class': 'btn btn-secondary',
                                    'data-dismiss': 'modal'
                                } ).append( mw.msg( 'practicegroups-cancel' ) )
                            )
                        )
                    )
                )
            );

            $( '#modalConfirmYes' ).click( function() { confirmedCallback.apply( this, callbackParams ); } );

            $( '#modalConfirm' ).modal( 'show' );
        },
        init: function() {
            mw.practiceGroups.common.addHandlers();
        },
        isValidEmail: function( email ) {
            return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test( email );
        },
        membershipButtonHandler: function( buttonElement, pgaction, useraction, callback ) {
            var buttonParams = $( buttonElement ).attr( 'id' ).split( '-' );
            var elementBase = buttonParams[ 0 ];
            var buttonAction = buttonParams[ 1 ];
            var buttonId = buttonParams[ 3 ];

            var practiceGroupsUserId = $( '#' + elementBase + '-' + buttonAction + '-practicegroupsuser_id-' + buttonId ).val();
            var practiceGroupId = $( '#' + elementBase + '-' + buttonAction + '-practicegroup_id-' + buttonId ).val();

            var apiParams = {
                action: 'practicegroups',
                pgaction: pgaction,
                useraction: useraction,
                practicegroupsuser_id: practiceGroupsUserId
            };

            if( pgaction === 'resendverificationemail' ) {
                delete apiParams.useraction;
            }

            var actionParams = {};

            if( buttonAction === 'acceptinvitation' ) {
                // No additional params needed
            } else if( buttonAction === 'approverequest' ) {
                // No additional params needed
            } else if( buttonAction === 'demoteadmin' ) {
                // No additional params needed
            } else if( buttonAction === 'inviteuser' ) {
                actionParams = {
                    practicegroup_id: practiceGroupId
                };

                var usernameOrEmail = $( '#practicegroups-inviteuser-search' ).val().trim();

                if( mw.practiceGroups.common.isValidEmail( usernameOrEmail ) ) {
                    actionParams.affiliated_email = usernameOrEmail;
                } else {
                    actionParams.user_name = usernameOrEmail;
                }
            } else if( buttonAction === 'join' ) {
                actionParams = {
                    practicegroup_id: practiceGroupId
                };
            } else if( buttonAction === 'promoteadmin' ) {
                // No additional params needed
            }

            $.extend( apiParams, actionParams );

            new mw.Api().postWithEditToken( apiParams ).then( function( result ) {
                callback( buttonId, result.practicegroups[ pgaction ] );
            } ).fail( function(a, b, c) {
                console.log( b );
                mw.practiceGroups.common.showAlert( mw.msg( 'practicegroups-error-generic' ), 'danger' );
            } );
        },
        showAlert: function( message, style, attachElement ) {
            style = style !== undefined ? style : 'info';

            if( attachElement === undefined ) {
                attachElement = $( '#nav-tabContent' ).length ? $( '#nav-tabContent' ) : $( '#bodyContent' );
            }

            $( '.alert' ).alert( 'close' );

            attachElement.prepend(
                $( '<div>', {
                    'class': 'alert alert-' + style + ' alert-dismissible fade show',
                    'role': 'alert'
                } ).append( message,
                    $( '<button>', {
                        'type': 'button',
                        'class': 'close',
                        'data-dismiss': 'alert',
                        'aria-label': mw.msg( 'practicegroups-close' )
                    } ).append( $( '<span>', {
                            'aria-hidden': 'true'
                        } ).append( '&times;' )
                    )
                )
            );
        },
        updateFieldValidation: function( fieldSelector, isValid, errorData ) {
            var formId = fieldSelector.closest( 'form' ).attr( 'id' );
            var fieldId = formId + '-' + fieldSelector.attr( 'name' ).replaceAll( '_', '' );

            if( isValid ) {
                fieldSelector.removeClass( 'is-invalid' );

                if( fieldSelector.is( ':not(:radio)' ) ) {
                    fieldSelector.addClass( 'is-valid' );
                }

                $( '#' + fieldId + '-invalid-feedback' ).html( '' );
            } else {
                if( errorData.message ) {
                    $( '#' + fieldId + '-invalid-feedback' ).html( errorData.message );
                } else if( errorData.error == mw.practiceGroups.common.validationErrorFailedValidation ) {
                    $( '#' + fieldId + '-invalid-feedback' ).html( mw.msg( 'practicegroups-error-failedvalidation' ) );
                } else if( errorData.error == mw.practiceGroups.common.validationErrorNotUnique ) {
                    console.log('woo');
                    $( '#' + fieldId + '-invalid-feedback' ).html( mw.msg( 'practicegroups-error-notunique',
                        fieldSelector.parent().children().first().text().toLowerCase() ) );
                } else if( errorData.error == mw.practiceGroups.common.validationErrorRequiredMissing ) {
                    $( '#' + fieldId + '-invalid-feedback' ).html( mw.msg( 'practicegroups-error-requiredmissing' ) );
                }

                fieldSelector.removeClass( 'is-valid' ).addClass( 'is-invalid' );
            }
        }
    };

    mw.practiceGroups.common.init();

}() );