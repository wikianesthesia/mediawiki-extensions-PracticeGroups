<?php


namespace PracticeGroups\Hook;

use ContribsPager;
use PracticeGroups\PracticeGroups;

class ContribsPager_getQueryInfo {
    public static function callback( ContribsPager &$pager, array &$queryInfo ) {
        $dbr = wfGetDB( DB_REPLICA );

        // Generate a list of practice groups/titles that the user has access to
        $allowedPracticeGroups = PracticeGroups::getAllAllowedPracticeGroups();

        $orQuery = '';

        if( count( $allowedPracticeGroups ) ) {
            foreach( $allowedPracticeGroups as $practiceGroup ) {
                $orQuery .= ' OR page_title' . $dbr->buildLike( $practiceGroup->getDBKey(), $dbr->anyString() );
            }
        }

        // TODO get public pages within private practice groups

        foreach( PracticeGroups::getPracticeGroupsNamespaces() as $practiceGroupsNamespace ) {
            $queryInfo[ 'conds' ][] = '( page_namespace != ' . wfGetDB( DB_REPLICA )->addQuotes( $practiceGroupsNamespace ) . $orQuery . ' )';
        }
    }
}