/**
 * @author Chris Rishel
 */
( function () {

    if( typeof mw === 'undefined' || mw === null ) {
        throw "";
    }

    mw.practiceGroups = mw.practiceGroups || {};

    mw.practiceGroups.special = {
        regexpIdIndex: /\[(\d+)\]/,
        init: function() {
            mw.practiceGroups.special.renderDataTables();

            $( '.practicegroups-rendershield' ).contents().unwrap();
        },
        renderDataTables: function() {
            if( $( '#table-allpracticegroups' ).length ) {
                $( '#table-allpracticegroups' ).DataTable( {
                    'columnDefs': [
                        { orderable: false, targets: 4 }
                    ],
                    'lengthChange': false,
                    'pageLength': 25,
                    'initComplete': function() {
                        $( '#table-allpracticegroups_filter' ).parent().parent().children().first().append( $( '#table-allpracticegroups_filter' ).css( 'float', 'left' ) );
                    }
                } );
            }
        }
    };

    mw.practiceGroups.special.init();

}() );