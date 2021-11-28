<?php


namespace PracticeGroups\Hook;

use PracticeGroups\PracticeGroups;
use RequestContext;
use SearchResult;
use SpecialSearch;

class ShowSearchHit {
    public static function callback( SpecialSearch $searchPage, SearchResult $result, $terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html ) {
        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $result->getTitle() );

        if( $practiceGroup) {
            $resultTitle = $result->getTitle();

            if( !$practiceGroup->userCanReadPage( $resultTitle->getArticleID() ) ) {
                return false;
            }

            if( $resultTitle->isSubpage() ) {
                $mainArticleTitle = PracticeGroups::getMainArticleTitle( $resultTitle );

                if( $mainArticleTitle ) {
                    $titleText = PracticeGroups::getPracticeGroupArticleDisplayTitle( $mainArticleTitle, $practiceGroup );

                    $link = preg_replace( '/^(.*>)PracticeGroup:([\w-]+)\/(.*)(<.*)$/', '$1' . $titleText . '$4', $link );
                }
            } else {
                $link = preg_replace( '/^(.*>)PracticeGroup:([\w-]+)(<.*)$/', '$1' . $practiceGroup->getFullName() . '$3', $link );
            }
        }
    }
}