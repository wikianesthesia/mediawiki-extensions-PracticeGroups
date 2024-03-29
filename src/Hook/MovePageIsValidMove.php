<?php

namespace PracticeGroups\Hook;

use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\PracticeGroups;
use RequestContext;
use Status;
use Title;

class MovePageIsValidMove {
    public static function callback( Title $oldTitle, Title $newTitle, Status $status ) {
        if( in_array( $oldTitle->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() ) ) {
            # The old title is in one of the practicegroups namespaces.

            if( $oldTitle->getNamespace() !== $newTitle->getNamespace() && !PracticeGroups::isUserPracticeGroupSysop() ) {
                # Practicegroups pages cannot change namespaces once created unless the user is a practice groups sysop

                $status->fatal( wfMessage( 'practicegroups-error-move-fromnamespace', $oldTitle->getNsText() ) );
            } elseif( !$oldTitle->isSubpage() ) {
                # This would essentially amount to renaming the practice group, which cannot be done from the move action
                # TODO this may need to be revised depending on how renaming works

                $status->fatal( wfMessage( 'practicegroups-error-move-practicegroup' ) );
            } elseif( !$newTitle->isSubpage() ) {
                # Can't move a subpage to a non-subpage. This would basically create a new orphaned practice group

                $status->fatal( 'practicegroups-error-move-fromsubpage', $oldTitle->getNsText() );
            } elseif( !$newTitle->isSubpage() ) {
                # Can't move a subpage to a non-subpage. This would basically create a new orphaned practice group

                $status->fatal( 'practicegroups-error-move-fromsubpage', $oldTitle->getNsText() );
            } elseif( $oldTitle->getRootText() !== $newTitle->getRootText() ) {
                # A practice group article may only be moved to a different group by a user who is an administrator of
                # the source practice group and at least an active member of the destination practice group
                # TODO this may need to be revised depending on how renaming works

                $user = RequestContext::getMain()->getUser();

                $oldPracticeGroup = PracticeGroup::getFromDBKey( $oldTitle->getRootText() );
                $oldPracticeGroupsUser = $oldPracticeGroup ?
                    PracticeGroups::getPracticeGroupsUserForUser( $oldPracticeGroup, $user ) : null;

                $newPracticeGroup = PracticeGroup::getFromDBKey( $newTitle->getRootText() );
                $newPracticeGroupsUser = $newPracticeGroup ?
                    PracticeGroups::getPracticeGroupsUserForUser( $newPracticeGroup, $user ) : null;

                if( !$oldPracticeGroupsUser
                    || !$oldPracticeGroupsUser->isAdmin()
                    || !$newPracticeGroupsUser
                    || !$newPracticeGroupsUser->isActive() ) {

                    $status->fatal( wfMessage( 'practicegroups-error-move-changepracticegroup' ) );
                }
            }
        } else {
            # The old title is not in one of the practicegroups namespaces

            if( in_array( $newTitle->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() )
                && ( !$newTitle->isSubpage() || !PracticeGroups::isUserPracticeGroupSysop() ) ) {
                # Pages from non-practicegroups namespaces cannot be moved into a practicegroups namespace unless the
                # new title is a subpage (i.e. not creating an orphaned practice group) and the user is a
                # practice groups sysop

                $status->fatal( wfMessage( 'practicegroups-error-move-tonamespace', $newTitle->getNsText() ) );
            }
        }
    }
}