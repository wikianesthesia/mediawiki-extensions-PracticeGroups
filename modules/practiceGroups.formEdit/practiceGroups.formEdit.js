( function () {

    if( typeof mw === 'undefined' || mw === null ) {
        throw "";
    }

    mw.practiceGroups = mw.practiceGroups || {};

    mw.practiceGroups.formEdit = {
        formId: 'form-practicegroup',
        prevNameShort: '',
        encodeFormData: function() {
            var formData = {};

            formData.practicegroup_id = $( '#' + mw.practiceGroups.formEdit.formId + '-practicegroupid' ).val();
            formData.dbkey = $( '#' + mw.practiceGroups.formEdit.formId + '-dbkey' ).val();
            formData.name_full = $( '#' + mw.practiceGroups.formEdit.formId + '-namefull' ).val();
            formData.name_short = $( '#' + mw.practiceGroups.formEdit.formId + '-nameshort' ).val();
            formData.color_primary = $( '#' + mw.practiceGroups.formEdit.formId + '-colorprimary' ).val();
            formData.color_secondary = $( '#' + mw.practiceGroups.formEdit.formId + '-colorsecondary' ).val();

            if( $( "input[name='preserve_main_title_links']:checked" ).val() == 1 ) {
                formData.preserve_main_title_links = '1';
            }

            if( $( "input[name='view_by_public']:checked" ).val() == 1 ) {
                formData.view_by_public = '1';
            }

            if( $( "input[name='join_by_public']:checked" ).val() == 1 ) {
                formData.join_by_public = '1';
            }

            if( $( "input[name='any_member_add_user']:checked" ).val() == 1 ) {
                formData.any_member_add_user = '1';
            }

            if( $( "input[name='join_by_request']:checked" ).val() == 1 ) {
                formData.join_by_request = '1';
            }

            if( $( "input[name='join_by_affiliated_email']:checked" ).val() == 1 ) {
                formData.join_by_affiliated_email = '1';
            }

            formData.affiliated_domains = $( '#form-practicegroup-affiliateddomains' ).val().replaceAll( /\s*\r?\n\s*/g, ',');

            return formData;
        },
        init: function() {
            $( '#form-practicegroup-namefull' ).on( 'change', function( event ) {
                var fieldName = 'name_full';

                mw.practiceGroups.formEdit.validateRemote( function( invalidFields ) {
                    if( invalidFields.hasOwnProperty( fieldName ) ) {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), false, invalidFields[ fieldName ] );
                    } else {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), true );
                    }
                } );
            } );

            $( '#form-practicegroup-nameshort' ).on( 'input', function( event ) {
                if( mw.practiceGroups.formEdit.makeValidDbKey( mw.practiceGroups.formEdit.prevNameShort ) == $( '#form-practicegroup-dbkey' ).val() ) {
                    $( '#form-practicegroup-dbkey' ).val( mw.practiceGroups.formEdit.makeValidDbKey( $( event.target ).val() ) );
                    $( '#form-practicegroup-dbkey' ).trigger( 'input' ).trigger( 'change' );

                    mw.practiceGroups.formEdit.prevNameShort = $( event.target ).val();
                }

                // TODO Api check to make sure name_short is unique

                var helpId = $( event.target ).attr( 'aria-describedby' );

                $( '#' + helpId ).html(
                    $( '#' + helpId ).html().replace( /\d+\//g, $( event.target ).val().length + '/' )
                );
            } ).on( 'change', function( event ) {
                var fieldName = 'name_short';

                mw.practiceGroups.formEdit.validateRemote( function( invalidFields ) {
                    if( invalidFields.hasOwnProperty( fieldName ) ) {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), false, invalidFields[ fieldName ] );
                    } else {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), true );
                    }
                } );
            } );

            $( '#form-practicegroup-dbkey' ).on( 'input', function( event ) {
                var dbKey = $( event.target ).val();

                var dbKeyValid = mw.practiceGroups.formEdit.makeValidDbKey( dbKey );

                if( dbKey != dbKeyValid ) {
                    $( event.target ).val( dbKeyValid );
                }

                // TODO Api check to make sure dbkey is unique

                var helpId = $( event.target ).attr( 'aria-describedby' );

                $( '#' + helpId ).html(
                    $( '#' + helpId ).html().replace( /\d+\//g, $( event.target ).val().length + '/' )
                );
            } ).on( 'change', function( event ) {
                var fieldName = 'dbkey';

                mw.practiceGroups.formEdit.validateRemote( function( invalidFields ) {
                    if( invalidFields.hasOwnProperty( fieldName ) ) {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), false, invalidFields[ fieldName ] );
                    } else {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), true );
                    }
                } );
            } );

            $( '#form-practicegroup-colorprimary' ).on( 'change', function( event ) {
                var fieldName = 'color_primary';

                mw.practiceGroups.formEdit.validateRemote( function( invalidFields ) {
                    if( invalidFields.hasOwnProperty( fieldName ) ) {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), false, invalidFields[ fieldName ] );
                    } else {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), true );
                    }
                } );
            } );

            $( '#form-practicegroup-colorsecondary' ).on( 'change', function( event ) {
                var fieldName = 'color_secondary';

                mw.practiceGroups.formEdit.validateRemote( function( invalidFields ) {
                    if( invalidFields.hasOwnProperty( fieldName ) ) {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), false, invalidFields[ fieldName ] );
                    } else {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), true );
                    }
                } );
            } );

            $( '[name="preserve_main_title_links"]' ).on( 'input', function( event ) {
                mw.practiceGroups.common.updateFieldValidation( $( '[name="preserve_main_title_links"]' ), true );
            } );

            $( '[name="view_by_public"]' ).on( 'input', function( event ) {
                mw.practiceGroups.common.updateFieldValidation( $( '[name="view_by_public"]' ), true );
            } );

            $( '[name="join_by_public"]' ).on( 'input', function( event ) {
                mw.practiceGroups.common.updateFieldValidation( $( '[name="join_by_public"]' ), true );

                mw.practiceGroups.formEdit.updateFormElementVisibility();
            } );

            $( '[name="any_member_add_user"]' ).on( 'input', function( event ) {
                mw.practiceGroups.common.updateFieldValidation( $( '[name="any_member_add_user"]' ), true );
            } );

            $( '[name="join_by_request"]' ).on( 'input', function( event ) {
                mw.practiceGroups.common.updateFieldValidation( $( '[name="join_by_request"]' ), true );
            } );

            $( '[name="join_by_affiliated_email"]' ).on( 'input', function( event ) {
                mw.practiceGroups.common.updateFieldValidation( $( '[name="join_by_affiliated_email"]' ), true );

                mw.practiceGroups.formEdit.updateFormElementVisibility();
            } );

            $( '#form-practicegroup-affiliateddomains' ).on( 'input', function() {
                $( this ).css( {
                    'height': 'auto'
                } ).height( this.scrollHeight );
            } ).on( 'change', function( event ) {
                var fieldName = 'affiliated_domains';

                mw.practiceGroups.formEdit.validateRemote( function( invalidFields ) {
                    if( invalidFields.hasOwnProperty( fieldName ) ) {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), false, invalidFields[ fieldName ] );
                    } else {
                        mw.practiceGroups.common.updateFieldValidation( $( event.target ), true );
                    }
                } );
            } );

            $( '#form-practicegroup-save' ).click( function() {
                $( '#form-practicegroup-save' ).prop( 'disabled', true );

                mw.practiceGroups.formEdit.submit();
            } );

            $( '#' + mw.practiceGroups.formEdit.formId ).submit( function( e ) {
                e.preventDefault();

                $( '#form-practicegroup-save' ).trigger( 'click' );
            } );

            mw.practiceGroups.formEdit.updateFormElementVisibility();
        },
        makeValidDbKey: function( dbKey ) {
            // Make sure the first character is a letter
            dbKey = dbKey.replace(/^[^A-Za-z]+(.*)/, '$1');

            if( dbKey ) {
                // Capitalize the first letter
                dbKey = dbKey[0].toUpperCase() + dbKey.substr(1);

                // Only allow special characters from a limited set
                var allowedExtraCharacters = '_-';

                // Remove any other characters
                dbKey = dbKey.replaceAll( new RegExp( '[^A-Za-z0-9' + allowedExtraCharacters + ']+', 'g'), '' );

                // Make sure allowed special characters only occur once in a row
                for( var i = 0; i < allowedExtraCharacters.length; i++ ) {
                    dbKey = dbKey.replaceAll( new RegExp( '[' + allowedExtraCharacters[ i ] + ']+', 'g' ), allowedExtraCharacters[ i ] );
                }
            }

            return dbKey;
        },
        submit: function() {
            var submitCallback = function() {
                var pgaction = 'edit';

                var apiParams = {
                    'action': 'practicegroups',
                    'pgaction': pgaction
                };

                $.extend( apiParams, mw.practiceGroups.formEdit.encodeFormData() );

                new mw.Api().postWithEditToken( apiParams ).then( function ( result ) {
                    if( result.practicegroups[ pgaction ].status === 'ok' ) {
                        if( apiParams.practicegroup_id == 0 ) {
                            location.href = mw.Title.newFromText( 'PracticeGroup:' + apiParams.dbkey ).getUrl();
                        } else {
                            location.reload();
                        }
                    } else {
                        mw.practiceGroups.common.showAlert( result.practicegroups[ pgaction ].message, 'danger' );

                        $( '#form-practicegroup-save' ).prop( 'disabled', false );
                    }
                } );
            };

            mw.practiceGroups.formEdit.validateForm( function() {
                if( $( '#form-practicegroup-practicegroupid' ).val() == 0 ) {
                    mw.practiceGroups.common.confirm(
                        mw.msg( 'practicegroups-form-practicegroup-create-confirm', $( '#form-practicegroup-namefull' ).val() ),
                        submitCallback
                    );
                } else {
                    submitCallback();
                }
            } );
        },
        updateFormElementVisibility: function() {
            if( $( '[name="join_by_public"]:checked' ).val() != 0 ) {
                $( '#form-practicegroup-anymemberadduser' ).collapse( 'hide' );
                $( '[name="any_member_add_user"]' ).prop( 'checked', false );

                $( '#form-practicegroup-joinbyrequest' ).collapse( 'hide' );
                $( '[name="join_by_request"]' ).prop( 'checked', false );

                $( '#form-practicegroup-joinbyaffiliatedemail' ).collapse( 'hide' );
                $( '[name="join_by_affiliated_email"]' ).prop( 'checked', false );

                $( '#form-practicegroup-affiliateddomains-container' ).collapse( 'hide' );
            } else {
                $( '#form-practicegroup-anymemberadduser' ).collapse( 'show' );
                $( '#form-practicegroup-joinbyrequest' ).collapse( 'show' );
                $( '#form-practicegroup-joinbyaffiliatedemail' ).collapse( 'show' );

                if( $( '[name="join_by_affiliated_email"]:checked' ).val() != 1 ) {
                    $( '#form-practicegroup-affiliateddomains-container' ).collapse( 'hide' );
                } else {
                    $( '#form-practicegroup-affiliateddomains-container' ).collapse( 'show' );
                }
            }
        },
        validateForm: function( callback ) {
            // Tidy up the form
            $( ':input' ).each( function() {
                $( this ).val( $.trim( $( this ).val( ) ) );
            } );

            // First make sure the data is valid from the server's perspective
            this.validateRemote( function( invalidFields ) {
                var isFormValid = true;

                var invalidFieldNames = Object.keys( invalidFields);
                if( invalidFieldNames.length ) {
                    for( var iFieldName in invalidFieldNames ) {
                        var invalidFieldName = invalidFieldNames[ iFieldName ];

                        mw.practiceGroups.common.updateFieldValidation(
                            $( '[name="' + invalidFieldName + '"]' ),
                            false,
                            invalidFields[invalidFieldName]
                        );
                    }

                    isFormValid = false;
                }

                // We still need to make sure that the user explicitly answered the appropriate
                // policy questions (since all undefined answers are valid from a database standpoint,
                // api validation won't check for this). However, we can use the same form validation
                // handling and just pretend we are the api.
                var fauxError = {
                    'error': mw.practiceGroups.common.validationErrorRequiredMissing
                };

                if( $( '[name="preserve_main_title_links"]:checked' ).val() === undefined ) {
                    mw.practiceGroups.common.updateFieldValidation( $( '[name="preserve_main_title_links"]' ), false, fauxError );

                    isFormValid = false;
                }

                if( $( '[name="view_by_public"]:checked' ).val() === undefined ) {
                    mw.practiceGroups.common.updateFieldValidation( $( '[name="view_by_public"]' ), false, fauxError );

                    isFormValid = false;
                }

                if( $( '[name="join_by_public"]:checked' ).val() === undefined ) {
                    mw.practiceGroups.common.updateFieldValidation( $( '[name="join_by_public"]' ), false, fauxError );

                    isFormValid = false;
                } else if( $( '[name="join_by_public"]:checked' ).val() == 0 ) {
                    // If the user said no to this question, more answers are required

                    if( $( '[name="any_member_add_user"]:checked' ).val() === undefined ) {
                        mw.practiceGroups.common.updateFieldValidation( $( '[name="any_member_add_user"]' ), false, fauxError );

                        isFormValid = false;
                    }

                    if( $( '[name="join_by_request"]:checked' ).val() === undefined ) {
                        mw.practiceGroups.common.updateFieldValidation( $( '[name="join_by_request"]' ), false, fauxError );

                        isFormValid = false;
                    }

                    if( $( '[name="join_by_affiliated_email"]:checked' ).val() === undefined ) {
                        mw.practiceGroups.common.updateFieldValidation( $( '[name="join_by_affiliated_email"]' ), false, fauxError );

                        isFormValid = false;
                    } else if( $( '[name="join_by_affiliated_email"]:checked' ).val() == 1 ) {
                        if( !$( '[name="affiliated_domains"]' ).val() ) {
                            mw.practiceGroups.common.updateFieldValidation( $( '[name="affiliated_domains"]' ), false, fauxError );

                            isFormValid = false;
                        }
                    }
                }

                if( isFormValid ) {
                    callback();
                }
            } );
        },
        validateRemote: function( callback ) {
            var parameters = {
                'action': 'practicegroups',
                'pgaction': 'editvalidate'
            };

            $.extend( parameters, mw.practiceGroups.formEdit.encodeFormData() );

            new mw.Api().get( parameters ).then( function ( data ) {
                var invalidFields = data.practicegroups.editvalidate.result.invalidFields;

                callback( invalidFields );
            } );
        }

    };

    mw.practiceGroups.formEdit.init();

}() );