<?php


namespace PracticeGroups\Hook;

use Html;
use MediaWiki\MediaWikiServices;
use PracticeGroups\PracticeGroups;
use Title;

class CategoryViewer_generateLink {
    public static function callback( string $type, Title $title, ?string $html, ?string &$link ) {
        if( PracticeGroups::isTitlePracticeGroupArticle( $title ) ) {
            if( !PracticeGroups::userCanReadPracticeGroupTitle( $title ) ) {
                $link = Html::element( 'i', [], wfMessage( 'practicegroups-linkprivate' )->text() );

                return;
            }

            // TODO this reformatted title won't get sorted under the correct letter. Would need some sort of hook
            // in CategoryViewer::addPage() to fix it.
            $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
            $link = $linkRenderer->makeLink( $title, PracticeGroups::getPracticeGroupArticleDisplayTitle( $title) );
        }
    }
}