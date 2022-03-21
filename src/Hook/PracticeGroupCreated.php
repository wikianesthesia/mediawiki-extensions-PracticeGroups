<?php

namespace PracticeGroups\Hook;

use CommentStoreComment;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\SlotRecord;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\PracticeGroups;
use RequestContext;
use Title;
use WikitextContent;

class PracticeGroupCreated {
    public static function callback( PracticeGroup $practiceGroup ) {
        global $wgPracticeGroupsHomepageTemplateTitle;

        $logger = PracticeGroups::getLogger();

        if( !$practiceGroup || !$practiceGroup->exists() ) {
            $logger->error(
                __METHOD__ . ': ' .
                wfMessage( 'practicegroups-createhomepage-practicegroupnotexist' )->text()
            );
            return;
        }

        $user = RequestContext::getMain()->getUser();

        if( !$user || !$user->isRegistered() ) {
            $logger->error(
                __METHOD__ . ': ' .
                wfMessage( 'practicegroups-createhomepage-usernotloggedin' )->text()
            );
            return;
        } elseif( !$practiceGroup->isUserActiveMember( $user ) ) {
            $logger->error(
                __METHOD__ . ': ' .
                wfMessage(
                    'practicegroups-createhomepage-usernotpracticegroupmember',
                    $user->getName(),
                    $practiceGroup->getFullName()
                )->text()
            );
            return;
        } elseif( !$wgPracticeGroupsHomepageTemplateTitle ) {
            $logger->error(
                __METHOD__ . ': ' .
                wfMessage( 'practicegroups-createhomepage-templatenotdefined' )->text()
            );
            return;
        }

        $homepageTemplateTitle = Title::newFromText( $wgPracticeGroupsHomepageTemplateTitle );

        if( !$homepageTemplateTitle->exists() ) {
            $logger->error(
                __METHOD__ . ': ' .
                wfMessage(
                    'practicegroups-createhomepage-templatenotexist',
                    $homepageTemplateTitle->getFullText()
                )->text()
            );

            return;
        }

        $wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();

        $homepageTemplatePage = $wikiPageFactory->newFromTitle( $homepageTemplateTitle );
        $homepageTemplateContent = $homepageTemplatePage->getContent()->getWikitextForTransclusion();

        $homepageTemplateContent = str_replace( '%NAME_FULL%', $practiceGroup->getFullName(), $homepageTemplateContent );
        $homepageTemplateContent = str_replace( '%NAME_SHORT%', $practiceGroup->getShortName(), $homepageTemplateContent );
        $homepageTemplateContent = str_replace( '%DBKEY%', $practiceGroup->getDBKey(), $homepageTemplateContent );

        $homepageTitle = $practiceGroup->getDashboardTitle();

        if( $homepageTitle->exists() ) {
            $logger->error(
                __METHOD__ . ': ' .
                wfMessage(
                    'practicegroups-createhomepage-homepagealreadyexists',
                    $homepageTitle->getFullText()
                )->text()
            );
            return;
        } elseif( !$homepageTitle->canExist() ) {
            $logger->error(
                __METHOD__ . ': ' .
                wfMessage(
                    'practicegroups-createhomepage-homepageinvalid',
                    $homepageTitle->getFullText()
                )->text()
            );
            return;
        }

        $homepagePage = $wikiPageFactory->newFromTitle( $homepageTitle );

        $summary = CommentStoreComment::newUnsavedComment( wfMessage( 'practicegroups-createhomepagefromtemplate-comment' )->text() );

        $homepageContent = new WikitextContent( $homepageTemplateContent );

        $updater = $homepagePage->newPageUpdater( $user );
        $updater->setContent( SlotRecord::MAIN, $homepageContent );
        $updater->saveRevision( $summary );

        if( !$updater->wasSuccessful() ) {
            $logger->error(
                __METHOD__ . ': ' .
                wfMessage(
                    'practicegroups-createhomepage-couldntcreatehomepage',
                    $homepageTitle->getFullText(),
                    $updater->getStatus()->getMessage()->text()
                )->text()
            );
            return;
        }

        $logger->info(
            __METHOD__ . ': ' .
            wfMessage(
                'practicegroups-createhomepage-homepagecreated',
                $homepageTitle->getFullText(),
                $practiceGroup->getFullName()
            )->text()
        );
    }
}