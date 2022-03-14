( function () {
    function escapeQuery( query ) {
        if( typeof query !== 'string' ) {
            return false;
        }

        return query
            .replace( /[|\\{}()[\]^$+*?.]/g, '\\$&' )
            .replace( /-/g, '\\x2d' );
    }

    function getPracticeGroupTitleHtml( prefixedTitle, query, highlightClass ) {
        var regexpPracticeGroupTitle = /PracticeGroup:([^\/]+)(\/(.*))?/;

        var regexpMatches = prefixedTitle.match( regexpPracticeGroupTitle );

        if( !regexpMatches ) {
            return false;
        }

        var practiceGroup = regexpMatches[ 1 ];
        var displayTitle = regexpMatches[ 3 ] !== undefined ? regexpMatches[ 3 ] : regexpMatches[ 1 ];

        var highlightRegexp = new RegExp( '(' + escapeQuery(query) + ')', 'i' );
        displayTitle = displayTitle.replace( highlightRegexp, '<span class="' + highlightClass + '">$1</span>' );

        var badgeAttribs = {
            'class': 'badge practicegroups-searchresults-badge'
        };

        var badgeStyle = '';

        if( $( '#practicegroup-data-' + practiceGroup ).length ) {
            var colorPrimary = $( '#practicegroup-data-' + practiceGroup ).attr( 'data-colorprimary' );

            if( colorPrimary ) {
                badgeStyle += 'background-color: ' + colorPrimary + ';';
            }

            var colorSecondary = $( '#practicegroup-data-' + practiceGroup ).attr( 'data-colorsecondary' );

            if( colorSecondary ) {
                badgeStyle += 'color: ' + colorSecondary + ';';
            }
        }

        if( badgeStyle ) {
            badgeAttribs[ 'style' ] = badgeStyle;
        }

        var $practiceGroupBadge = $( '<h6>', {} ).append(
            $( '<span>', badgeAttribs ).append( practiceGroup )
        );

        return $practiceGroupBadge[0].innerHTML + displayTitle;
    }

    function titleWidgetHandler( data ) {
        if( data.action === 'impression-results' ) {
            // We need a tiny delay to change the html of the ooui widget or it will get changed back immediately
            setTimeout( function() {
                var query = data.query;

                $( '.mw-widget-titleWidget-menu > .mw-widget-titleOptionWidget a' ).each( function() {
                    var practiceGroupTitleHtml = getPracticeGroupTitleHtml( $( this ).text(), query, 'oo-ui-labelElement-label-highlight' );

                    if( practiceGroupTitleHtml ) {
                        $( this ).html( practiceGroupTitleHtml );
                    }
                } );
            }, 1 );
        }
    }

    mw.trackSubscribe( 'mediawiki.searchSuggest', function ( topic, data ) {
        if( data.action === 'impression-results' ) {
            var query = data.query;

            $( '.suggestions-result' ).each( function() {
                var practiceGroupTitleHtml = getPracticeGroupTitleHtml( $( this ).text(), query, 'highlight' );

                if( practiceGroupTitleHtml ) {
                    $( this ).html( practiceGroupTitleHtml );
                }
            } );
        }
    } );

    mw.trackSubscribe( 'mw.widgets.SearchInputWidget', function ( topic, data ) {
        titleWidgetHandler( data );
    } );

    mw.trackSubscribe( 'mw.widgets.TitleWidget', function ( topic, data ) {
        titleWidgetHandler( data );
    } );

}() );