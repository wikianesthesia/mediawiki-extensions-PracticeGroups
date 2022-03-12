<?php

namespace PracticeGroups\Hook;

use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\Page\PracticeGroupDashboard;
use PracticeGroups\PracticeGroups;
use OutputPage;
use Skin;

/**
 * Class BeforePageDisplay
 * @package PracticeGroups\Hook
 */
class BeforePageDisplay {
    /**
     * @param OutputPage $out
     * @param Skin $skin
     */
    public static function callback( OutputPage &$out, Skin &$skin ) {
        $out->addModules( 'ext.practiceGroups.searchSuggest' );

        $title = $out->getTitle();

        $practiceGroup = PracticeGroup::getFromDBKey( $title->getRootText() );

        if( !$practiceGroup ) {
            return;
        }

        if( $title->getNamespace() === NS_PRACTICEGROUP ) {
            if( !$title->isSubpage() ) {
                $practiceGroupPage = new PracticeGroupDashboard( $practiceGroup, $out );

                $practiceGroupPage->execute();
            } else {
                $out->setPageTitle( PracticeGroups::getPracticeGroupArticleDisplayTitle( PracticeGroups::getMainArticleTitle( $title )->getText(), $practiceGroup ) );
            }
        } elseif( $title->getNamespace() === NS_PRACTICEGROUP_TALK ) {
            if( $title->isSubpage() ) {
                $out->setPageTitle( wfMessage( 'practicegroups-talk-articletitle', PracticeGroups::getMainArticleTitle( $title )->getText(), $practiceGroup->getShortName() )->plain() );
            } else {
                $out->setPageTitle( wfMessage( 'practicegroups-talk-grouptitle', (string)$practiceGroup )->plain() );
            }
        }
    }
}