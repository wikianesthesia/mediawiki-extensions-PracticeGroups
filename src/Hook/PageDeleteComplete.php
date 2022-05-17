<?php

namespace PracticeGroups\Hook;

use ManualLogEntry;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use PracticeGroups\PracticeGroups;
use Title;

class PageDeleteComplete {
    public static function callback( ProperPageIdentity $page, Authority $deleter, string $reason, int $pageID, RevisionRecord $deletedRev, ManualLogEntry $logEntry, int $archivedRevisionCount ) {
        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( Title::makeTitle( $page->getNamespace(), $page->getDBkey() ) );

        if( $practiceGroup ) {
            $practiceGroup->purgeArticles();
        }
    }
}