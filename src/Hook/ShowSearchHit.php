<?php


namespace PracticeGroups\Hook;

use BootstrapUI\BootstrapUI;
use HtmlArmor;
use MediaWiki\MediaWikiServices;
use PracticeGroups\PracticeGroups;
use SearchResult;
use SpecialSearch;

class ShowSearchHit {
    public static function callback( SpecialSearch $searchPage, SearchResult $result, $terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html ) {
        $title = $result->getTitle();
        $titleText = $title->getText();

        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        if( $practiceGroup ) {
            if( !$practiceGroup->userCanReadPage( $title->getArticleID() ) ) {
                return false;
            }

            if( $title->isSubpage() ) {
                $mainArticleTitle = PracticeGroups::getMainArticleTitle( $title );

                if( $mainArticleTitle ) {
                    $titleText = $mainArticleTitle->getText();
                }
            } else {
                $titleText = $practiceGroup->getFullName();
            }

            $searchQuery = $searchPage->getRequest()->getText('search');
            $searchWords = explode( ' ', $searchQuery );

            foreach( $searchWords as $searchWord ) {
                $titleText = preg_replace( '/(' . preg_quote( $searchWord, '/' ) . ')/i', '<span class="searchmatch">$1</span>', $titleText );
            }

            $badgeHtml = PracticeGroups::getPracticeGroupBadge( $practiceGroup, 'practicegroups-searchhit-badge' );

            $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

            $link = $linkRenderer->makeKnownLink( $title, new HtmlArmor( $titleText . $badgeHtml ) );
        }
    }
}