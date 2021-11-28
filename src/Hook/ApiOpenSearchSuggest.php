<?php


namespace PracticeGroups\Hook;

use PracticeGroups\PracticeGroups;
use RequestContext;

class ApiOpenSearchSuggest {
    public static function callback( &$results ) {
        # Make sure user has access to any practice group titles already found by opensearch
        foreach( $results as $articleId => $result ) {
            if( isset( $result[ 'title' ] ) && PracticeGroups::isTitlePracticeGroupArticle( $result[ 'title' ] ) ) {
                if( !PracticeGroups::userCanReadPracticeGroupTitle( $result[ 'title' ] ) ) {
                    unset( $results[ $articleId ] );
                }
            }
        }

        $search = RequestContext::getMain()->getRequest()->getText( 'search' );

        $practiceGroupArticleTitles = PracticeGroups::searchPracticeGroupArticleTitles( $search );

        if( !$practiceGroupArticleTitles ) {
            return;
        }

        foreach( $practiceGroupArticleTitles as $practiceGroupArticleTitle ) {
            $results[ $practiceGroupArticleTitle->getArticleID() ] = [
                'title' => $practiceGroupArticleTitle,
                'redirect from' => null,
                'extract' => false,
                'extract trimmed' => false,
                'image' => false,
                'url' => wfExpandUrl( $practiceGroupArticleTitle->getFullURL(), PROTO_CURRENT ),
                'displaytitle' => PracticeGroups::getPracticeGroupArticleDisplayTitle( $practiceGroupArticleTitle->getPrefixedText() )
            ];
        }

        usort( $results, function( $a, $b ) {
            $displayTitleA = $a[ 'displaytitle' ] ?? $a[ 'title' ]->getPrefixedText();
            $displayTitleB = $b[ 'displaytitle' ] ?? $b[ 'title' ]->getPrefixedText();

            return ( $displayTitleA < $displayTitleB ) ? -1 : 1;
        } );
    }
}