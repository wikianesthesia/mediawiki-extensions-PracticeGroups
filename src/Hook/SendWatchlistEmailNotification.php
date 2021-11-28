<?php


namespace PracticeGroups\Hook;

use EmailNotification;
use PracticeGroups\PracticeGroups;
use Title;
use User;

class SendWatchlistEmailNotification {
    public static function callback( User $watchingUser, Title $title, EmailNotification $emailNotification ) {
        if( PracticeGroups::isTitlePracticeGroupArticle( $title ) ) {
            if( !PracticeGroups::userCanReadPracticeGroupTitle( $title ) ) {
                return false;
            }
        }
    }
}