<?php

namespace PracticeGroups\Hook;

use PracticeGroups\PracticeGroups;
use SkinTemplate;
use Title;

class PersonalUrls {
    public static function callback( array &$personal_urls, Title $title, SkinTemplate $skin ) {
        $user = $skin->getUser();

        if( !$user->isRegistered() ) {
            return;
        }

        $practiceGroupsUsers = PracticeGroups::getPracticeGroupsUsersForUser( $user );

        $practiceGroupsNotificationCount = 0;

        foreach( $practiceGroupsUsers as $practiceGroupsUser ) {
            if( $practiceGroupsUser->isInvited() ) {
                $practiceGroupsNotificationCount++;
            }
        }

        $practiceGroupsPersonalUrls = [
            'practicegroups' => [
                'text' => wfMessage( 'practicegroups-action' )->text(),
                'href' => Title::newFromText( 'Special:PracticeGroups')->getLinkURL(),
                'notificationCount' => $practiceGroupsNotificationCount
            ]
        ];

        foreach( $practiceGroupsUsers as $practiceGroupsUser ) {
            if( !$practiceGroupsUser->isActive() ) {
                continue;
            }
            
            $practiceGroup = $practiceGroupsUser->getPracticeGroup();

            $practiceGroupUserNotificationCount = $practiceGroupsUser->isAdmin() ? count( $practiceGroup->getRequestedPracticeGroupsUsers() ) : 0;

            $practiceGroupsPersonalUrls[ 'practicegroup_' . $practiceGroup->getDBKey() ] = [
                'text' => $practiceGroup->getShortName(),
                'href' => $practiceGroup->getLinkURL() .
                    ( $practiceGroupUserNotificationCount ? '#members' : '' ),
                'notificationCount' => $practiceGroupUserNotificationCount
            ];
        }

        $personal_urls = array_merge(
            array_slice( $personal_urls, 0, count( $personal_urls ) - 1, true ),
            $practiceGroupsPersonalUrls,
            array_slice( $personal_urls, count( $personal_urls ) - 1, 1, true )
        );
    }
}