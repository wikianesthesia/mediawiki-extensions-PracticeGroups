<?php

namespace PracticeGroups\Hook;

use PracticeGroups\PracticeGroups;

class ChangesListSpecialPageQuery {
    public static function callback( $name, &$tables, &$fields, &$conds, &$query_options, &$join_conds, $opts ) {
        $dbr = wfGetDB( DB_REPLICA );

        // Generate a list of practice groups/titles that the user has access to
        $allowedPracticeGroups = PracticeGroups::getAllAllowedPracticeGroups();

        $orQuery = '';

        if( count( $allowedPracticeGroups ) ) {
            foreach( $allowedPracticeGroups as $practiceGroup ) {
                $orQuery .= ' OR rc_title' . $dbr->buildLike( $practiceGroup->getDBKey(), $dbr->anyString() );
            }
        }

        // TODO get public pages within private practice groups

        foreach( PracticeGroups::getPracticeGroupsNamespaces() as $practiceGroupsNamespace ) {
            $conds[] = '( rc_namespace != ' . $dbr->addQuotes( $practiceGroupsNamespace ) . $orQuery . ' )';
        }
    }
}
