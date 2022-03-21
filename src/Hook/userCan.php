<?php


namespace PracticeGroups\Hook;

use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\PracticeGroups;
use Title;
use User;

class userCan {

    /**
     * userCan is a bit confusing. Essentially we want to return true and not change $result if we have no opinion about
     * what should happen. Most of the permissions handling is done in getUserPermissionErrors, the only thing we're
     * doing here is explicitly allowing move and delete for members of practice groups.
     *
     * @param Title $title
     * @param User $user
     * @param $action
     * @param $result
     * @return false
     */
    public static function callback( Title $title, User $user, $action, &$result ) {
        if( in_array( $title->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() )
            && $title->isSubpage()
            && $user->isRegistered()) {
            $practiceGroup = PracticeGroup::getFromDBKey( $title->getRootText() );

            if( $practiceGroup ) {
                $practiceGroupsUser = $practiceGroup->getPracticeGroupsUserForUser( $user );

                if( $practiceGroupsUser && $practiceGroupsUser->isActive() ) {
                    $allowedPracticeGroupUserActions = [
                        'delete',
                        'move',
                        'move-subpages'
                    ];

                    if( in_array( $action, $allowedPracticeGroupUserActions ) ) {
                        # Allow the action
                        $result = true;

                        # Do not consult any other permissions checkers
                        return false;
                    }
                }
            }
        }

        return true;

    }
}