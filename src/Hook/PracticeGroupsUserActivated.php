<?php

namespace PracticeGroups\Hook;

use PracticeGroups\DatabaseClass\PracticeGroupsUser;

class PracticeGroupsUserActivated {
    public static function callback( PracticeGroupsUser $practiceGroupsUser ) {
        global $wgPracticeGroupsConfirmMatchingMWEmailOnVerify;

        $mwUser = $practiceGroupsUser->getUser();

        # Depending on configuration, confirm MediaWiki user email if it matches the practice group email
        if( $wgPracticeGroupsConfirmMatchingMWEmailOnVerify
            && $mwUser
            && $mwUser->getId()
            && !$mwUser->isEmailConfirmed()
            && $mwUser->getEmail()
            && strtolower( $practiceGroupsUser->getAffiliatedEmail() ) == strtolower( $mwUser->getEmail() ) ) {
            $mwUser->confirmEmail();
            $mwUser->saveSettings();
        }
    }
}