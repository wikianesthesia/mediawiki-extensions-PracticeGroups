<?php

namespace PracticeGroups\Hook;

use ApiMain;
use FauxRequest;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;
use PracticeGroups\PracticeGroups;
use Title;

class PageMoveComplete {
    public static function callback( LinkTarget $old, LinkTarget $new, UserIdentity $userIdentity, int $pageid, int $redirid, string $reason, RevisionRecord $revision  ) {
        $movePageFactory = MediaWikiServices::getInstance()->getMovePageFactory();
        $logger = LoggerFactory::getInstance( PracticeGroups::getExtensionName() );

        if( in_array( $old->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() ) ) {
            # TODO will eventually need to deal with renaming practice groups

            # Prevent infinite recursion

            return;
        }

        // TODO can this be done without FauxRequesting the API?
        // Regrettably, we can't use list=search since the search index might not reliably be updated, so
        // pages might be missed. This could get expensive when the site gets big...

        foreach( PracticeGroups::getPracticeGroupsNamespaces() as $practiceGroupsNamespace ) {
            $resultData = [
                'continue' => [
                    'apcontinue' => ''
                ]
            ];

            while( isset( $resultData[ 'continue' ] ) ) {
                $request = [
                    'action' => 'query',
                    'list' => 'allpages',
                    'apnamespace' => $practiceGroupsNamespace,
                    'apcontinue' => $resultData[ 'continue' ][ 'apcontinue' ],
                    'aplimit' => 'max'
                ];

                $fauxReq = new FauxRequest( $request );

                $module = new ApiMain( $fauxReq );
                $module->execute();

                $resultData = $module->getResult()->getResultData();

                $pageData = $module->getResult()->getResultData( [ 'query', 'allpages' ] );

                // Iterate through all pages in the PracticeGroup namespace to find subpages which match the moved article title
                foreach( $pageData as $pageResult ) {
                    if( isset( $pageResult[ 'pageid' ] ) ) {
                        $practiceGroupsPageTitle = Title::newFromID( $pageResult[ 'pageid' ] );

                        // Only subpages are relevant since they might match the moved article title
                        if( $practiceGroupsPageTitle->isSubpage() ) {

                            // See if the subpage text matches the old article title
                            if( $old->getText() == PracticeGroups::getMainArticleTitle( $practiceGroupsPageTitle )->getText() ) {

                                // Create a new title for the subpage which matches the new article title
                                $newPracticeGroupsPageTitle = Title::makeTitleSafe( $pageResult[ 'ns' ], $practiceGroupsPageTitle->getRootText() . '/' . $new->getText() );

                                $movePage = $movePageFactory->newMovePage( $practiceGroupsPageTitle, $newPracticeGroupsPageTitle );

                                $moveValidResult = $movePage->isValidMove();

                                if( !$newPracticeGroupsPageTitle->exists() && $moveValidResult->isOK() ) {
                                    // Move the subpage. Redirects are important to keep any links in practice group pages working

                                    $logger->debug( 'Attempting to move subpage from {old} to {new} in namespace {ns}', [
                                        'old' => $old->getText(),
                                        'new' => $new->getText(),
                                        'ns' => $pageResult[ 'ns' ]
                                    ] );

                                    $movePage->move( $userIdentity, wfMessage( 'practicegroups-move-reason-mainarticlemoved' )->text(), false );
                                } else {
                                    # TODO i18n
                                    $errorDetails = '';

                                    if( $newPracticeGroupsPageTitle->exists() ) {
                                        $errorDetails = 'New page already exists';
                                    } elseif( !$moveValidResult->isOK() ) {
                                        $errorDetails = $moveValidResult->getMessage()->plain();
                                    }

                                    $logger->warning( 'Could not move subpage from {old} to {new} in namespace {ns}: {details}', [
                                        'old' => $old->getText(),
                                        'new' => $new->getText(),
                                        'ns' => $pageResult[ 'ns' ],
                                        $errorDetails
                                    ] );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}