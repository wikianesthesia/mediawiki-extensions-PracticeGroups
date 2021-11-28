<?php

namespace PracticeGroups\Hook;

use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\PracticeGroups;
use Title;
use User;

class getUserPermissionsErrors {
    public static function callback( Title $title, User $user, $action, &$result ) {
        if( in_array( $title->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() ) ) {
            $practiceGroup = PracticeGroup::getFromDBKey( $title->getRootText() );

            if( !$practiceGroup ) {
                $result = wfMessage( 'practicegroups-error-practicegroup-notfound', $title->getRootText() );

                return false;
            }

            if( $action === 'read' && $title->exists() ) {
                if( $practiceGroup->userCanReadPage( $title->getArticleID() ) ) {
                    $result = true;

                    return true;
                } else {
                    $result = 'practicegroups-error-permissiondenied';

                    return false;
                }
            }

            if( !$user->isLoggedIn() ) {
                $result = false;

                return false;
            }

            $practiceGroupsUser = $practiceGroup->getPracticeGroupsUserForUser( $user->getId() );

            if( ( !$practiceGroupsUser || !$practiceGroupsUser->isActive() ) ) {
                $result = 'practicegroups-error-permissiondenied';

                return false;
            }

            if( !$title->isSubpage() ) {
                if( $action === 'delete' ) {
                    $result = 'practicegroups-error-deletepage';

                    return false;
                } elseif( $action === 'move' ) {
                    $result = 'practicegroups-error-movepage';

                    return false;
                }
            }

            if( $practiceGroupsUser && $practiceGroupsUser->isActive() ) {
                $allowedPracticeGroupUserActions = [
                    'delete',
                    'move',
                    'move-subpages'
                ];

                if( in_array( $action, $allowedPracticeGroupUserActions ) ) {
                    $result = true;

                    return true;
                }
            }
        }
    }
}
