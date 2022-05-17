<?php

namespace PracticeGroups\Hook;

use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use PracticeGroups\PracticeGroups;
use WikiPage;

class PageSaveComplete {
    public static function callback( WikiPage $wikiPage, UserIdentity $user, string $summary, int $flags, RevisionRecord $revisionRecord, EditResult $editResult ) {
        // If a new page is being created the page is a practice group article, purge the articles cache for the practice group
        if( !$revisionRecord->getParentId() ) {
            $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $wikiPage->getTitle() );

            if( $practiceGroup ) {
                $practiceGroup->purgeArticles();
            }
        }
    }
}