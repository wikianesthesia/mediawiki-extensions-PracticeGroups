<?php


namespace PracticeGroups\Hook;

use Html;
use PracticeGroups\PracticeGroups;
use Skin;
use OutputPage;

class SkinSubPageSubtitle {
    public static function callback( &$subpages, Skin $skin, OutputPage $out ) {
        $title = $out->getTitle();

        if( !in_array( $title->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() ) || !$title->isSubpage() ) {
            return true;
        }

        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        $html = '';

        $breadcrumbTitle = $title->getBaseTitle();

        while( $breadcrumbTitle->exists() ) {
            if( $practiceGroup->userCanReadPage( $breadcrumbTitle->getArticleID() ) ) {
                if( !$breadcrumbTitle->isSubpage() ) {
                    $displayTitle = wfMessage( 'practicegroups-practicegroupnamearticles', $practiceGroup->getFullName() )->text();

                    $html = '&#8592;&nbsp;' . Html::rawElement( 'a', [
                            'href' => $practiceGroup->getLinkURL() . '#articles',
                            'title' => $displayTitle
                        ], $displayTitle ) . '&nbsp;' . $html;
                } else {
                    $displayTitle = PracticeGroups::getPracticeGroupArticleDisplayTitle( $breadcrumbTitle );

                    $html = '&#8592;&nbsp;' . Html::rawElement( 'a', [
                            'href' => $breadcrumbTitle->getLinkURL(),
                            'title' => $displayTitle
                        ], $displayTitle ) . '&nbsp;' . $html;
                }
            }

            if( !$breadcrumbTitle->isSubpage() ) {
                break;
            }

            $breadcrumbTitle = $breadcrumbTitle->getBaseTitle();
        }

        $subpages = $html;

        return false;
    }
}