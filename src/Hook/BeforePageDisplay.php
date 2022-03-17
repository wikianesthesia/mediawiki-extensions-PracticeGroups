<?php

namespace PracticeGroups\Hook;

use Html;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\DatabaseClass\PracticeGroupsUser;
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

        $practiceGroupsUsers = PracticeGroupsUser::getAllForUser( $out->getUser()->getId() );

        foreach( $practiceGroupsUsers as $practiceGroupsUser ) {
            $practiceGroup = $practiceGroupsUser->getPracticeGroup();

            $out->addHTML( Html::rawElement( 'div' , [
                'id' => 'practicegroup-data-' . $practiceGroup->getDBKey(),
                'data-id' => $practiceGroup->getId(),
                'data-colorprimary' => $practiceGroup->getPrimaryColor(),
                'data-colorsecondary' => $practiceGroup->getSecondaryColor(),
            ] ) );
        }

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
                $out->setPageTitle( wfMessage( 'practicegroups-talk-articletitle', PracticeGroups::getMainArticleTitle( $title )->getText(), $practiceGroup->getShortName() )->text() );
            } else {
                $out->setPageTitle( wfMessage( 'practicegroups-talk-grouptitle', (string)$practiceGroup )->text() );
            }
        }
    }
}