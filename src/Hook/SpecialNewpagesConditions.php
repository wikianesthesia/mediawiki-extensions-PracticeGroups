<?php


namespace PracticeGroups\Hook;

use FormOptions;
use NewPagesPager;
use PracticeGroups\PracticeGroups;

class SpecialNewpagesConditions {
    public static function callback( NewPagesPager &$pager, FormOptions $opts, array &$conds, array &$tables, array &$fields, array &$joinConds ) {
        $dbr = wfGetDB( DB_REPLICA );

        // Generate a list of practice groups/titles that the user has access to
        $allowedPracticeGroups = PracticeGroups::getAllAllowedPracticeGroups();

        $orQuery = '';

        if( count( $allowedPracticeGroups ) ) {
            foreach( $allowedPracticeGroups as $practiceGroup ) {
                $orQuery .= ' OR page_title' . $dbr->buildLike( $practiceGroup->getDBKey(), $dbr->anyString() );
            }
        }

        // TODO get public pages for private practice groups

        foreach( PracticeGroups::getPracticeGroupsNamespaces() as $practiceGroupsNamespace ) {
            $conds[] = '( page_namespace != ' . wfGetDB( DB_REPLICA )->addQuotes( $practiceGroupsNamespace ) . $orQuery . ' )';
        }
    }
}