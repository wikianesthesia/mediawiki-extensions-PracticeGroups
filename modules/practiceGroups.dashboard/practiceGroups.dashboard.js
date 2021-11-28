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
        practiceGroupDBKey: null,
        regexPracticeGroup: /PracticeGroup(Notes)?:([\w\d_-]+)\/?(.*)/,
        notesSearchResultSuffix: ' (Notes)',
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
                    'id': 'modalConfirmLabel'
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
            mw.practiceGroups.dashboard.practiceGroupDBKey = window.location.href.match( mw.practiceGroups.dashboard.regexPracticeGroup )[ 2 ];

            mw.practiceGroups.dashboard.addHandlers();

            mw.practiceGroups.dashboard.renderDataTables();

            $( '.practicegroups-rendershield' ).contents().unwrap();
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