<?php


namespace PracticeGroups\Hook;

use PracticeGroups\PracticeGroups;
use PracticeGroups\DatabaseClass\PracticeGroupsPageSetting;
use Title;

class TitleGetEditNotices
{
    public static function callback( Title $title, int $oldid, array &$notices ) {
        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        if( $practiceGroup ) {
            if( !$practiceGroup->canViewByPublic() ) {
                $privacy = PracticeGroups::getEffectivePrivacyForPage( $title->getArticleID() );

                if( $privacy == PracticeGroupsPageSetting::PRIVACY_PUBLIC ) {
                    $notices[] = wfMessage( 'practicegroups-privacy-publictitlenotice', $practiceGroup->getShortName() )->text();
                }
            }
        }
    }
}