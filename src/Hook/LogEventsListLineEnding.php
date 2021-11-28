<?php


namespace PracticeGroups\Hook;

use DatabaseLogEntry;
use Html;
use LogEventsList;
use PracticeGroups\PracticeGroups;

class LogEventsListLineEnding {
    public static function callback( LogEventsList $page, string &$line, DatabaseLogEntry &$entry, array &$classes, array &$attribs ) {
        $allowedPracticeGroups = PracticeGroups::getAllAllowedPracticeGroups();

        // TODO get public pages within private practice groups

        $title = $entry->getTarget();
        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        if( $practiceGroup && !array_key_exists( $practiceGroup->getId(), $allowedPracticeGroups ) ) {
            $line = preg_replace( '/(<.*)/m', Html::rawElement( 'i', [], wfMessage( 'practicegroups-logprivate' )->text() ), $line, 1 );
        }
    }
}