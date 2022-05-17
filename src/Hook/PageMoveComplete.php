<?php

namespace PracticeGroups\Hook;

use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;
use PracticeGroups\PracticeGroups;
use Status;
use Title;

class PageMoveComplete {
    public static function callback( LinkTarget $old, LinkTarget $new, UserIdentity $userIdentity, int $pageid, int $redirid, string $reason, RevisionRecord $revision  ) {
        // Purge the cache of articles for the old and/or new titles if either is a practice group article
        $oldPracticeGroup = null;

        if( in_array( $old->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() ) ) {
            $oldPracticeGroup = PracticeGroups::getPracticeGroupFromTitle( Title::newFromLinkTarget( $old ) );

            if( $oldPracticeGroup ) {
                $oldPracticeGroup->purgeArticles();
            }
        }

        if( in_array( $new->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() ) ) {
            $newPracticeGroup = PracticeGroups::getPracticeGroupFromTitle( Title::newFromLinkTarget( $new ) );

            if( $newPracticeGroup && ( !$oldPracticeGroup || $oldPracticeGroup->getId() !== $newPracticeGroup->getId() ) ) {
                $newPracticeGroup->purgeArticles();
            }
        }

        // If both the old and new titles are in the main namespace, may need to move practice group articles to
        // preserve linkages with the article title from the main namespace
        if( $old->getNamespace() !== NS_MAIN || $new->getNamespace() !== NS_MAIN ) {
            return;
        }

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );

        $table = 'page';
        $vars = [
            'page_namespace',
            'page_title'
        ];

        $namespaceCond = '';
        foreach( PracticeGroups::getPracticeGroupsNamespaces() as $practiceGroupsNamespace ) {
            if( $namespaceCond ) {
                $namespaceCond .= ' OR ';
            }
            $namespaceCond .= 'page_namespace = ' . $dbr->addQuotes( $practiceGroupsNamespace );
        }

        // These two like conditions will search for titles which are subpages with exactly one level of depth
        $conds = [
            $namespaceCond,
            'page_title' . $dbr->buildLike( $dbr->anyString(), '/' . $old->getDBkey() ),
            'page_title NOT' . $dbr->buildLike( $dbr->anyString(), '/', $dbr->anyString(), '/', $dbr->anyString() )
        ];

        $res = $dbr->select(
            $table,
            $vars,
            $conds,
            __METHOD__
        );

        foreach( $res as $row ) {
            $oldPracticeGroupTitle = Title::newFromText( $row->page_title, NS_PRACTICEGROUP );
            $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $oldPracticeGroupTitle );

            // Don't move pages if the practice group doesn't exist or is configured to not preserve main title links
            if( !$practiceGroup || !$practiceGroup->preserveMainTitleLinks() ) {
                continue;
            }

            // Get any subpages for the existing title.
            // Getting now since not sure if getSubpages() will still work after the move.
            $oldPracticeGroupTitleSubpages = $oldPracticeGroupTitle->getSubpages();

            // Create a new title for the subpage which matches the new article title
            $newPracticeGroupTitle = Title::makeTitleSafe( $row->page_namespace, $practiceGroup->getDBKey() . '/' . $new->getText() );

            $moveResult = static::movePage( $oldPracticeGroupTitle, $newPracticeGroupTitle, $userIdentity );

            if( $moveResult && count( $oldPracticeGroupTitleSubpages ) ) {
                foreach( $oldPracticeGroupTitleSubpages as $oldPracticeGroupTitleSubpage ) {
                    $newPracticeGroupTitleSubpageText = str_replace(
                        $oldPracticeGroupTitle->getText(),
                        $newPracticeGroupTitle->getText(),
                        $oldPracticeGroupTitleSubpage->getText()
                    );

                    $newPracticeGroupTitleSubpage = Title::makeTitleSafe( $row->page_namespace, $newPracticeGroupTitleSubpageText );

                    // Don't bother checking the result since we should keep trying the other subpages even if one fails.
                    static::movePage( $oldPracticeGroupTitleSubpage, $newPracticeGroupTitleSubpage, $userIdentity );
                }
            }
        }
    }

    protected static function movePage( Title $oldTitle, Title $newTitle, UserIdentity $userIdentity ): Status {
        $movePageFactory = MediaWikiServices::getInstance()->getMovePageFactory();
        $logger = LoggerFactory::getInstance( PracticeGroups::getExtensionName() );

        $movePage = $movePageFactory->newMovePage( $oldTitle, $newTitle );
        $moveValidResult = $movePage->isValidMove();

        $loggerContext = [
            'old' => $oldTitle->getFullText(),
            'new' => $newTitle->getFullText(),
        ];

        // Make sure the move is valid (will check to make sure new title doesn't already exist)
        if( $moveValidResult->isOK() ) {
            $logger->debug( 'Attempting to move page from {old} to {new} to preserve main title link', $loggerContext );

            // Move the subpage. Redirects make things complicated (e.g. can't move and then move back)
            return $movePage->move( $userIdentity, wfMessage( 'practicegroups-move-reason-maintitlemoved' )->text(), false );
        } else {
            $loggerContext[ 'details' ] = $moveValidResult->getMessage()->text();

            $logger->error( 'Could not move subpage from {old} to {new}: {details}', $loggerContext );

            return $moveValidResult;
        }
    }
}