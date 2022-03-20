<?php


namespace PracticeGroups\Hook;

use ApiBase;
use MediaWiki\MediaWikiServices;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\PracticeGroups;

class APIQueryAfterExecute {

    protected static $allowedResultKeys = [
        'ns',
        'pageid',
        'title'
    ];

    public static function callback( ApiBase &$module ) {
        $result = $module->getResult();
        $resultData = $result->getResultData();

        # For some reason when in generator mode, this can get called several times. It has something to do with
        # thumbnail processing. Regardless, I think it's probably fine to only do processing on the first call
        # and set a flag to skip subsequent calls.
        if( isset( $resultData[ '_practicegroups' ] ) ) {
            return;
        }

        self::onPrefixSearch( $module );
        self::filterResultsPermissions( $module );

        $result->addValue( [ '_practicegroups' ], null, 1 );
    }



    public static function filterResultsPermissions( ApiBase &$module ) {
        $apiResult = $module->getResult();
        $resultData = $apiResult->getResultData();

        if( !isset( $resultData[ 'query' ] ) ) {
            return;
        }

        $user = $module->getUser();

        $namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();

        $canonicalNamespaceNames = [];

        foreach( PracticeGroups::getPracticeGroupsNamespaces() as $namespace ) {
            $canonicalNamespaceNames[] = $namespaceInfo->getCanonicalName( $namespace );
        }

        foreach( $resultData[ 'query' ] as $list => $results ) {
            $path = [
                'query',
                $list
            ];

            foreach( $results as $resultKey => $result ) {
                if( isset( $result[ 'title'] ) && PracticeGroups::isTitlePracticeGroupArticle( $result[ 'title' ] ) ) {
                    $blockResult = !PracticeGroups::userCanReadPracticeGroupTitle( $result[ 'title' ], $user );

                    if( $blockResult ) {
                        $apiResult->removeValue( $path, $resultKey );

                        # It is important that any user be able to at least see that the page exists through the api
                        # to handle things like page moves.

                        $allowedResult = [];

                        foreach( self::$allowedResultKeys as $allowedResultKey ) {
                            if( isset( $result[ $allowedResultKey ] ) ) {
                                $allowedResult[ $allowedResultKey ] = $result[ $allowedResultKey ];
                            }
                        }

                        $apiResult->addValue( [ 'query', $list ], null, $allowedResult );
                    }
                }
            }
        }
    }



    /**
     * This function adds practice group articles to search results when using prefixsearch
     *
     * @param ApiBase $module
     */
    public static function onPrefixSearch( ApiBase &$module ) {
        $request = $module->getRequest();
        $result = $module->getResult();
        $resultData = $result->getResultData();

        # The result path is different depending on whether queries from list or generator
        $reqList = $request->getText( 'list' );
        $reqGenerator = $request->getText( 'generator' );

        if( $reqList !== 'prefixsearch' && $reqGenerator !== 'prefixsearch' ) {
            return;
        }

        if( $reqGenerator ) {
            $generator = true;
            $paramPrefix = 'gps';
            $moduleName = 'pages';
        } else {
            $generator = false;
            $paramPrefix = 'ps';
            $moduleName = 'prefixsearch';
        }

        $results = $resultData[ 'query' ][ $moduleName ];
        unset( $results[ '_element' ] );
        unset( $results[ '_type' ] );

        $search = $request->getText( $paramPrefix . 'search' );
        $limit = $request->getText( $paramPrefix . 'limit' );

        # TODO implement limit/continue
        if( $limit === 'max' ) {
            $limit = 5000;
        } elseif( !$limit || $limit < 1 || $limit > 5000 ) {
            $limit = 10;
        }

        $practiceGroupArticleTitles = PracticeGroups::searchPracticeGroupArticleTitles( $search );

        if( !$practiceGroupArticleTitles ) {
            return;
        }

        if( $generator ) {
            foreach( $practiceGroupArticleTitles as $practiceGroupArticleTitle ) {
                $results[ $practiceGroupArticleTitle->getArticleID()] = [
                    'pageid' => $practiceGroupArticleTitle->getArticleID(),
                    'ns' => NS_PRACTICEGROUP,
                    'title' => $practiceGroupArticleTitle->getPrefixedText(),
                    'index' => count( $results ) + 1,
                    'displaytitle' => PracticeGroups::getPracticeGroupArticleDisplayTitle( $practiceGroupArticleTitle->getPrefixedText() )
                ];
            }

            # Sort by title. This removes some of the smarts of the underlying search, but it's not a huge deal.
            $resultsSort = [];

            foreach( $results as $articleId => $resultTitle ) {
                $resultsSort[ $articleId ] = $resultTitle[ 'displaytitle' ] ?? $resultTitle[ 'title' ];
            }

            asort( $resultsSort );
            $index = 1;

            foreach( $resultsSort as $articleId => $displayTitle ) {
                $results[ $articleId ][ 'index' ] = $index;
                $index++;
            }
        } else {
            foreach( $practiceGroupArticleTitles as $practiceGroupArticleTitle ) {
                $results[] = [
                    'ns' => NS_PRACTICEGROUP,
                    'title' => $practiceGroupArticleTitle->getPrefixedText(),
                    'pageid' => $practiceGroupArticleTitle->getArticleID(),
                    'displaytitle' => PracticeGroups::getPracticeGroupArticleDisplayTitle( $practiceGroupArticleTitle->getPrefixedText() )
                ];
            }

            usort( $results, function( $a, $b ) {
                $displayTitleA = $a[ 'displaytitle' ] ?? $a[ 'title' ];
                $displayTitleB = $b[ 'displaytitle' ] ?? $b[ 'title' ];

                return ( $displayTitleA < $displayTitleB ) ? -1 : 1;
            } );
        }

        # TODO this breaks thumbnails
        //$results = array_slice( $results, 0, $limit );

        $result->reset();

        $result->addValue( [ 'query' ], $moduleName, $results );
    }
}