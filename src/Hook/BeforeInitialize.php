<?php

namespace PracticeGroups\Hook;

use MediaWiki;
use OutputPage;
use PracticeGroups\PracticeGroups;
use Title;
use User;
use UserNotLoggedIn;
use WebRequest;

class BeforeInitialize {
    public static function callback( Title &$title, $unused, OutputPage $output, User $user, WebRequest $request, MediaWiki $mediaWiki ) {
        $title = $output->getTitle();
        $user = $output->getUser();

        if( PracticeGroups::isTitlePracticeGroupArticle( $title ) &&
            !PracticeGroups::userCanReadPracticeGroupTitle( $title ) &&
            !$user->isRegistered() ) {
            throw new UserNotLoggedIn();
        }
    }
}