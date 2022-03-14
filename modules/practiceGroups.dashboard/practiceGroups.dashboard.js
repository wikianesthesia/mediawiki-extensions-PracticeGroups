/**
 * @author Chris Rishel
 */
( function () {

    if( typeof mw === 'undefined' || mw === null ) {
        throw "";
    }

    mw.practiceGroups = mw.practiceGroups || {};

    mw.practiceGroups.dashboard = {
        createDialogExistingPracticeGroupTitles: [],
        createDialogExistingPublicTitles: [],
        nsPracticeGroup: 7740,
        nsPracticeGroupNotes: 7742,
        practiceGroupId: null,
        practiceGroupDBKey: null,
        regexPracticeGroup: /PracticeGroup:([\w\d_-]+)(\/(.*))?/,
        addHandlers: function() {
            $( '.nav-link-main' ).on( 'shown.bs.tab', function() {
                if( window.location.hash !== '' ) {
                    $( '#footer-info' ).attr( 'style', 'display: none !important;' );
                } else {
                    $( '#footer-info' ).attr( 'style', 'display: flex !important;' );
                }
            } );

            $( '#practicegroup-createarticle-button' ).click( function() {
                mw.practiceGroups.dashboard.createArticleClick();
            } );

            $( '#practicegroup-massinvite-button' ).click( function() {
                mw.practiceGroups.dashboard.massInviteClick();
            } );
        },
        createArticleClick: function() {
            var existingPublicResultsDefault = $( '<i>' ).append( mw.msg( 'practicegroups-practicegroup-articles-create-dialog-existingpublicgrouparticles-noresults' ) );
            var existingPracticeGroupResultsDefault = $( '<i>' ).append( mw.msg( 'practicegroups-practicegroup-articles-create-dialog-existingpracticegrouparticles-noresults' ) );

            var modalContent = $( '<div>' ).append( $( '<div>', {
                    'class': 'form-group'
                } ).append( $( '<label>', {
                    'for': '#modalCreateTitle'
                } ).append( mw.msg( 'practicegroups-practicegroup-articles-create-dialog-label' )
                ), $( '<input>', {
                    'type': 'text',
                    'class': 'form-control',
                    'id': 'modalCreateTitle',
                    'aria-describedby': 'modalCreateTitleHelp',
                    'autocomplete': 'off'
                } ), $( '<small>', {
                    'id': 'modalCreateTitleHelp',
                    'class': 'form-text text-muted'
                } ).append( mw.msg( 'practicegroups-practicegroup-articles-create-dialog-help' )
                ) ), $( '<div>', {
                    'id': 'modalCreateExistingPublic'
                } ).append( mw.msg( 'practicegroups-practicegroup-articles-create-dialog-existingpublicgrouparticles'
                ), $( '<div>', {
                    'id': 'modalCreateExistingPublicResults',
                    'class': 'm-3'
                } ).append( existingPublicResultsDefault
                ) ), $( '<div>', {
                    'id': 'modalCreateExistingPracticeGroup'
                } ).append( mw.msg( 'practicegroups-practicegroup-articles-create-dialog-existingpracticegrouparticles'
                ), $( '<div>', {
                    'id': 'modalCreateExistingPracticeGroupResults',
                    'class': 'm-3'
                } ).append( existingPracticeGroupResultsDefault
                ) ) );

            $( '#modalCreate' ).remove();

            $( '#bodyContent' ).prepend( $( '<div>', {
                    'class': 'modal fade',
                    'id': 'modalCreateArticle',
                    'tabindex': '-1',
                    'role': 'dialog',
                    'aria-labelledby': 'modalCreateLabel',
                    'aria-hidden': 'true'
                } ).append( $( '<div>', {
                    'class': 'modal-dialog',
                    'role': 'document'
                } ).append( $( '<div>', {
                    'class': 'modal-content'
                } ).append( $( '<div>', {
                    'class': 'modal-header'
                } ).append( $( '<h5>', {
                    'class': 'modal-title pt-0',
                    'id': 'modalCreateLabel'
                } ).append( mw.msg( 'practicegroups-practicegroup-articles-create-dialog-header' )
                ), $( '<button>', {
                    'type': 'button',
                    'class': 'close',
                    'data-dismiss': 'modal',
                    'aria-label': 'Close'
                } ).append( $( '<span>', {
                    'aria-hidden': true
                } ).append( '&times;'
                ) ) ), $( '<div>', {
                    'class': 'modal-body'
                } ).append( modalContent ),
                $( '<div>', {
                    'class': 'modal-footer'
                } ).append( $( '<button>', {
                        'type': 'button',
                        'class': 'btn btn-primary',
                        'data-dismiss': 'modal',
                        'disabled': true,
                        'id': 'modalCreateProceed'
                    } ).append( mw.msg( 'practicegroups-practicegroup-articles-create-dialog-proceedbutton' ) ),
                    $( '<button>', {
                        'type': 'button',
                        'class': 'btn btn-secondary',
                        'data-dismiss': 'modal'
                    } ).append( mw.msg( 'practicegroups-cancel' ) )
                )))));

            $( '#modalCreateProceed' ).click( function() {
                $( '#modalCreateTitle' ).val( $( '#modalCreateTitle' ).val().trim() );

                window.location.href = mw.Title.newFromText(
                    'PracticeGroup:' + mw.practiceGroups.dashboard.practiceGroupDBKey + '/' + $( '#modalCreateTitle' ).val()
                ).getUrl() + '?veaction=edit';
            } );

            $( '#modalCreateTitle' ).on( 'input', function() {
                if( $( this ).val() ) {
                    $( this ).val( $( this ).val().charAt( 0 ).toUpperCase() + $( this ).val().slice( 1 ) );

                    $( '#modalCreateProceed' ).prop( 'disabled', false );

                    var maxRows = 5;

                    var api = new mw.Api();

                    api.get( {
                        'action': 'query',
                        'list': 'search',
                        'srsearch': 'intitle:' + $( '#modalCreateTitle').val(),
                        'srnamespace': '0',
                        'srprop': '',
                        'srlimit': maxRows
                    } ).done( function ( apiResult ) {
                        mw.practiceGroups.dashboard.createDialogExistingPracticeGroupTitles = [];
                        mw.practiceGroups.dashboard.createDialogExistingPublicTitles = [];

                        var iResult = 0;

                        var existingPublicTitleList = $( '<div>', {
                            'class': 'practicegroups-createarticle-searchresults'
                        } );

                        while( mw.practiceGroups.dashboard.createDialogExistingPublicTitles.length < maxRows && iResult < apiResult.query.search.length ) {
                            if( apiResult.query.search[ iResult ].hasOwnProperty( 'title' ) ) {
                                mw.practiceGroups.dashboard.createDialogExistingPublicTitles.push( apiResult.query.search[ iResult ].title );

                                existingPublicTitleList.append(
                                    $( '<a>', {
                                        'href': mw.Title.newFromText(
                                            'PracticeGroup:' + mw.practiceGroups.dashboard.practiceGroupDBKey + '/' + apiResult.query.search[ iResult ].title
                                        ).getUrl() + '?veaction=edit'
                                    } ).append( apiResult.query.search[ iResult ].title ), '<br/>'
                                );
                            }

                            iResult++;
                        }

                        var existingPracticeGroupTitleList = $( '<div>', {
                            'class': 'practicegroups-createarticle-searchresults'
                        } );

                        var tableArticlesData = $( '#table-articles' ).DataTable().data().toArray();

                        for( var i in tableArticlesData ) {
                            var existingArticleTitle = tableArticlesData[ i ][ 0 ].replace( /(<([^>]+)>)/gi , "");
                            var existingArticleTitleWords = existingArticleTitle.toLowerCase().split( ' ' );

                            var newTitleWords = $( '#modalCreateTitle').val().toLowerCase().split( ' ' );

                            var titleMatch = false;

                            for( var iExistingArticleTitleWord in existingArticleTitleWords ) {
                                for( var iNewTitleWord in newTitleWords ) {
                                    if( existingArticleTitleWords[ iExistingArticleTitleWord ] === newTitleWords[ iNewTitleWord ] ) {
                                        titleMatch = true;

                                        break;
                                    }
                                }
                            }

                            if( titleMatch ) {
                                mw.practiceGroups.dashboard.createDialogExistingPracticeGroupTitles.push( existingArticleTitle );

                                existingPracticeGroupTitleList.append( existingArticleTitle, '<br/>' );
                            }
                        }

                        $( '#modalCreateExistingPublicResults' ).html( mw.practiceGroups.dashboard.createDialogExistingPublicTitles.length
                            ? existingPublicTitleList
                            : existingPublicResultsDefault
                        );

                        $( '#modalCreateExistingPracticeGroupResults' ).html( mw.practiceGroups.dashboard.createDialogExistingPracticeGroupTitles.length
                            ? existingPracticeGroupTitleList
                            :existingPracticeGroupResultsDefault
                        );
                    } );
                } else {
                    $( '#modalCreateExistingPublicResults' ).html( existingPublicResultsDefault );
                    $( '#modalCreateExistingPracticeGroupResults' ).html( existingPracticeGroupResultsDefault );

                    $( '#modalCreateProceed' ).prop( 'disabled', true );
                }
            } ).on( 'keyup', function( e ) {
                if( e.key === 'Enter' ) {
                    $( '#modalCreateProceed' ).trigger( 'click' );
                }
            } );

            if( $( '#practicegroup-articles-search' ).val() ) {
                $( '#modalCreateTitle' ).val( $( '#practicegroup-articles-search' ).val() );
                $( '#modalCreateTitle' ).trigger( 'input' );
            }

            $( '#modalCreateArticle' )
                .on( 'shown.bs.modal', function() {
                    $( '#modalCreateTitle' ).focus();
                } )
                .modal( 'show' );
        },
        init: function() {
            mw.practiceGroups.dashboard.practiceGroupDBKey = window.location.href.match( mw.practiceGroups.dashboard.regexPracticeGroup )[ 1 ];
            mw.practiceGroups.dashboard.practiceGroupId = $( '#practicegroup-data-' + mw.practiceGroups.dashboard.practiceGroupDBKey ).attr( 'data-id' );

            mw.practiceGroups.dashboard.addHandlers();

            mw.practiceGroups.dashboard.renderDataTables();

            $( '.practicegroups-rendershield' ).contents().unwrap();
        },
        massInviteClick: function() {
            var modalContent = $( '<div>' ).append( $( '<div>', {
                'class': 'form-group'
            } ).append( $( '<label>', {
                'for': '#modalMassInviteInput'
            } ).append( mw.msg( 'practicegroups-practicegroup-massinvite-dialog-label' )
            ), $( '<textarea>', {
                'class': 'form-control',
                'id': 'modalMassInviteInput',
                'aria-describedby': 'modalMassInviteHelp',
                'autocomplete': 'off',
                'rows': 10
            } ), $( '<small>', {
                'id': 'modalMassInviteHelp',
                'class': 'form-text text-muted'
            } ).append( mw.msg( 'practicegroups-practicegroup-massinvite-dialog-help' )
            ) ) );

            $( '#modalMassInvite' ).remove();

            $( '#bodyContent' ).prepend( $( '<div>', {
                'class': 'modal fade',
                'id': 'modalMassInvite',
                'tabindex': '-1',
                'role': 'dialog',
                'aria-labelledby': 'modalMassInviteLabel',
                'aria-hidden': 'true'
            } ).append( $( '<div>', {
                'class': 'modal-dialog',
                'role': 'document'
            } ).append( $( '<div>', {
                'class': 'modal-content'
            } ).append( $( '<div>', {
                    'class': 'modal-header'
                } ).append( $( '<h5>', {
                    'class': 'modal-title pt-0',
                    'id': 'modalMassInviteLabel'
                } ).append( mw.msg( 'practicegroups-practicegroup-massinvite-dialog-header' )
                ), $( '<button>', {
                    'type': 'button',
                    'class': 'close',
                    'data-dismiss': 'modal',
                    'aria-label': 'Close'
                } ).append( $( '<span>', {
                    'aria-hidden': true
                } ).append( '&times;'
                ) ) ), $( '<div>', {
                    'class': 'modal-body'
                } ).append( modalContent ),
                $( '<div>', {
                    'class': 'modal-footer'
                } ).append( $( '<button>', {
                        'type': 'button',
                        'class': 'btn btn-primary',
                        'disabled': true,
                        'id': 'modalMassInviteSendInvites'
                    } ).append( mw.msg( 'practicegroups-practicegroup-massinvite-dialog-sendinvites' ) ),
                    $( '<button>', {
                        'type': 'button',
                        'class': 'btn btn-secondary',
                        'data-dismiss': 'modal'
                    } ).append( mw.msg( 'practicegroups-cancel' ) )
                )))));

            $( '#modalMassInviteSendInvites' ).click( function() {
                mw.practiceGroups.dashboard.massInviteSendInvites();
            } );

            $( '#modalMassInviteInput' ).on( 'input', function() {
                if( $( this ).val() ) {
                    $( '#modalMassInviteSendInvites' ).prop( 'disabled', false );
                } else {
                    $( '#modalMassInviteSendInvites' ).prop( 'disabled', true );
                }
            } );

            $( '#modalMassInvite' )
                .on( 'shown.bs.modal', function() {
                    $( '#modalMassInviteInput' ).focus();
                } )
                .modal( 'show' );
        },
        massInviteSendInvites: function() {
            if( !$( '#modalMassInviteInput').length ) {
                return;
            }

            $( '#modalMassInvite .alert' ).remove();

            // Get element to attach alert messages to
            var alertTarget = $( '#modalMassInvite .modal-body' );

            var emails = [];
            var usernames = [];
            var invalidEmails = [];

            // Get user input
            var inviteList = $( '#modalMassInviteInput').val();

            // Support line-delimited
            var inviteListLines = inviteList.split( /\r?\n/ );

            for( var iLine in inviteListLines ) {
                // Support comma-delimited
                var inviteListUsers = inviteListLines[ iLine ].split( ',' );
                for( var iUser in inviteListUsers ) {
                    // If the user contains an @, assume it's an email, otherwise assume it's a MediaWiki username
                    if( inviteListUsers[ iUser ].indexOf( '@' ) > -1 ) {
                        if( mw.practiceGroups.common.isValidEmail( inviteListUsers[ iUser ] ) ) {
                            emails.push( inviteListUsers[ iUser ] );
                        } else {
                            invalidEmails.push( inviteListUsers[ iUser ] );
                        }
                    } else {
                        usernames.push( inviteListUsers[ iUser ] );
                    }
                }
            }

            if( invalidEmails.length ) {
                mw.practiceGroups.common.showAlert(
                    mw.msg( 'practicegroups-practicegroup-massinvite-dialog-error-invalidemails', invalidEmails.join( '<br/>' ) ),
                    'danger',
                    alertTarget );

                return;
            }

            var confirmSendInvites = function( emails, usernames ) {
                var practiceGroupId = mw.practiceGroups.dashboard.practiceGroupId;

                if( !practiceGroupId ) {
                    return;
                }

                var userCount = emails.length + usernames.length;

                var doInvitesApiQuery = function() {
                    $( '#modalMassInviteSendInvites' ).prop( 'disabled', true );

                    var resultedUsers = 0;
                    var invitedUsers = 0;
                    var errors = [];

                    alertTarget.prepend( $( '<div>', {
                        'class': 'progress'
                    } ).append( $( '<div>', {
                        'id': 'modalMassInviteProgress',
                        'class': 'progress-bar progress-bar-striped progress-bar-animated',
                        'role': 'progressbar',
                        'aria-valuenow': 0,
                        'aria-valuemin': 0,
                        'aria-valuemax': userCount
                    } ) ) );

                    var showResults = function() {
                        $( '#modalMassInviteProgress' ).parent().remove();

                        var success = invitedUsers === resultedUsers;
                        var alertStyle = success ? 'success' : 'danger';
                        var message = success ?
                            mw.msg( 'practicegroups-practicegroup-massinvite-dialog-result-success', invitedUsers, mw.practiceGroups.dashboard.practiceGroupDBKey ) :
                            mw.msg( 'practicegroups-practicegroup-massinvite-dialog-result-error', invitedUsers, errors.join( '<br/>' ) );

                        mw.practiceGroups.common.showAlert( message, alertStyle, alertTarget );

                        $( '#modalMassInvite' )
                            .on( 'hidden.bs.modal', function() {
                                location.reload();
                            } );
                    };

                    var updateProgress = function() {
                        var width = 100 * resultedUsers / userCount;

                        $( '#modalMassInviteProgress' ).css( 'width', width + '%' );

                        if( resultedUsers === userCount ) {
                            showResults();
                        }
                    };

                    var apiParams = {
                        'action': 'practicegroups',
                        'pgaction': 'edituser',
                        'useraction': 'inviteuser',
                        'practicegroup_id': practiceGroupId,
                        'practicegroupsuser_id': 0
                    };

                    var currentUser = 1;

                    for( let iEmail in emails ) {
                        setTimeout( function() {
                            new mw.Api().postWithEditToken(
                                Object.assign( apiParams, {
                                    'affiliated_email': emails[ iEmail ]
                                } )
                            ).then( function( result ) {
                                resultedUsers++;

                                if( result.practicegroups.edituser.status === 'error' ) {
                                    errors.push( emails[ iEmail ] + ': ' + result.practicegroups.edituser.message );
                                } else {
                                    invitedUsers++;
                                }

                                updateProgress();
                            } ).fail( function( a, b, c ) {
                                errors.push( emails[ iEmail ] + ': ' + b.error.info );
                                resultedUsers++;
                                updateProgress();
                            } );
                        }, ( currentUser - 1 ) * 1000 / mw.config.get( 'wgPracticeGroupsEmailMaxRate' ) );

                        currentUser++;
                    }

                    for( let iUsernames in usernames ) {
                        setTimeout( function() {
                            new mw.Api().postWithEditToken(
                                Object.assign( apiParams, {
                                    'user_name': usernames[ iUsernames ]
                                } )
                            ).then( function( result ) {
                                console.log( result );
                                resultedUsers++;

                                if( result.practicegroups.edituser.status === 'error' ) {
                                    errors.push( usernames[ iUsernames ] + ': ' + result.practicegroups.edituser.message );
                                } else {
                                    invitedUsers++;
                                }

                                updateProgress();
                            } ).fail( function( a, b, c ) {
                                errors.push( usernames[ iUsernames ] + ': ' + b.error.info );
                                resultedUsers++;
                                updateProgress();
                            } );
                        }, ( currentUser - 1 ) * 1000 / mw.config.get( 'wgPracticeGroupsEmailMaxRate' ) );

                        currentUser++;
                    }
                };

                mw.practiceGroups.common.confirm(
                    mw.msg( 'practicegroups-practicegroup-massinvite-dialog-confirm', userCount, mw.practiceGroups.dashboard.practiceGroupDBKey ),
                    doInvitesApiQuery
                );
            };

            if( usernames.length ) {
                var queryUserApiParams = {
                    'action': 'query',
                    'list': 'users',
                    'ususers': usernames.join( '|' )
                };

                new mw.Api().get( queryUserApiParams ).then( function( response ) {
                    var validUsernames = [];
                    var invalidUsernames = [];

                    for( var iResult in response.query.users ) {
                        if( response.query.users[ iResult ].hasOwnProperty( 'userid' ) ) {
                            validUsernames.push( response.query.users[ iResult ].name );
                        } else {
                            invalidUsernames.push( response.query.users[ iResult ].name );
                        }
                    }

                    if( invalidUsernames.length ) {
                        mw.practiceGroups.common.showAlert(
                            mw.msg( 'practicegroups-practicegroup-massinvite-dialog-error-usernames', invalidUsernames.join( '<br/>' ) ),
                            'danger',
                            alertTarget );

                        return;
                    } else {
                        confirmSendInvites( emails, validUsernames );
                    }
                } ).fail( function( a, b, c ) {
                    console.log( b );
                } );
            } else {
                confirmSendInvites( emails, [] );
            }
        },
        renderDataTables: function() {
            if( $( '#table-pendinginvitations' ).length ) {
                $( '#table-pendinginvitations' ).DataTable( {
                    'lengthChange': false,
                    'ordering': false,
                    'pageLength': 10,
                    'initComplete': function() {
                        $( '#table-pendinginvitations_filter' ).parent().parent().children().first().append( $( '#table-pendinginvitations_filter' ).css( 'float', 'left' ) );
                    }
                } );
            }

            if( $( '#table-activemembers' ).length ) {
                $( '#table-activemembers' ).DataTable( {
                    'lengthChange': false,
                    'ordering': false,
                    'pageLength': 10,
                    'initComplete': function() {
                        $( '#table-activemembers_filter' ).parent().parent().children().first().append( $( '#table-activemembers_filter' ).css( 'float', 'left' ) );
                    }
                } );
            }

            if( $( '#table-articles' ).length ) {
                $( '#table-articles' ).DataTable( {
                    'lengthChange': false,
                    'ordering': false,
                    'pageLength': 15,
                    'initComplete': function() {
                        $( '#table-articles_filter' ).parent().parent().append( $( '#table-articles_filter' ) );

                        $( '#table-articles_filter' ).siblings().remove();

                        $( '#table-articles_filter label' ).contents()[0].nodeValue = '';

                        $( '#table-articles_filter input')
                            .attr( {
                                'id': 'practicegroup-articles-search',
                                'autocomplete': 'off'
                            } )
                            .removeClass( 'form-control-sm' )
                            .unwrap()
                            .unwrap();

                        $( '#practicegroup-articles-search' ).parent()
                            .addClass( 'no-gutters' )
                            .children().wrapAll( $( '<div>', {
                                'class': 'col mb-2'
                            } )
                        );

                        ;
                    }
                } );
            }
        }
    };

    mw.practiceGroups.dashboard.init();

}() );