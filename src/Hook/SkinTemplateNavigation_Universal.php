<?php

namespace PracticeGroups\Hook;

use MediaWiki\MediaWikiServices;
use PracticeGroups\PracticeGroups;
use PracticeGroups\DatabaseClass\PracticeGroupsUser;
use SkinTemplate;
use Title;

class SkinTemplateNavigation_Universal {
    public static function callback( SkinTemplate $skinTemplate, array &$links ) {

        $title = $skinTemplate->getRelevantTitle();
        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        if( !$title->exists() || !$practiceGroup ) {
            return;
        }

        if( $skinTemplate->getUser()->isRegistered() ) {
            $practiceGroupsUser = $practiceGroup->getPracticeGroupsUserForUser( $skinTemplate->getUser() );

            if( $practiceGroupsUser && $practiceGroupsUser->isAdmin() ) {
                $request = $skinTemplate->getRequest();

                $links[ 'actions' ][ 'privacy' ] = [
                    'class' => $request->getVal( 'action' ) === 'privacy' ? 'selected' : false,
                    'text' => wfMessage( 'practicegroups-action-privacy' )->text(),
                    'href' => $title->getLocalURL( 'action=privacy' )
                ];
            }
        }

        return;




        // NO LONGER USED
        return;

        global $wgArticlePath;



        if( $skinTemplate->getUser()->isRegistered() ) {
            $title = $skinTemplate->getRelevantTitle();

            if( in_array( $title->getNamespace(), PracticeGroups::getPracticeGroupsNamespaces() ) ) {
                if( $title->isSubpage() ) {
                    // If the current article is in the PracticeGroup namespace, set up the namespace links to
                    // refer back to the main article and the main article's talk page.
                    $mainArticleTitle = PracticeGroups::getMainArticleTitle( $title );

                    $mainTitleNamespaceText = $mainArticleTitle->getNsText() ? $mainArticleTitle->getNsText() : 'Main';

                    if( in_array( $mainTitleNamespaceText, $wgPracticeGroupsNotesEnabledNamespaces )
                        && !in_array( $mainArticleTitle->getDBkey(), $wgPracticeGroupsNotesBlacklistTitles ) ) {

                        $newNamespaceLinks = [ 'main' => [
                            'class' => '',
                            'text' => wfMessage( 'practicegroups-mainarticle' )->text(),
                            'href' => $mainArticleTitle->getLinkURL(),
                            'exist' => $mainArticleTitle->exists(),
                            'primary' => true,
                            'context' => 'subject',
                            'id' => 'ca-nstab-main'
                        ]
                        ];

                        if( $mainArticleTitle->canHaveTalkPage() ) {
                            $talkTitle = Title::newFromText( 'Talk:' . $mainArticleTitle->getText() );

                            $newNamespaceLinks[ 'talk' ] = [
                                'class' => '',
                                'text' => wfMessage( 'practicegroups-maintalk' )->text(),
                                'href' => $talkTitle->getLinkURL(),
                                'exist' => $talkTitle->exists(),
                                'primary' => true,
                                'context' => 'talk',
                                'rel' => 'discussion',
                                'id' => 'ca-talk'
                            ];
                        }

                        $links[ 'namespaces' ] = $newNamespaceLinks + $links[ 'namespaces' ];

                        if( isset( $links[ 'namespaces' ][ 'practicegroup' ] ) ) {
                            unset( $links[ 'namespaces' ][ 'practicegroup' ] );
                        }

                        if( isset( $links[ 'namespaces' ][ 'practicegroup_talk' ] ) ) {
                            unset( $links[ 'namespaces' ][ 'practicegroup_talk' ] );
                        }

                        # Set the title to the main article to add practice group links
                        $title = $mainArticleTitle;
                    }
                } else {
                    $links[ 'namespaces' ][ 'practicegroup' ][ 'text' ] = wfMessage( 'practicegroups-practicegroup' )->text();
                }
            } elseif( $title->getNamespace() == NS_PRACTICEGROUP_TALK ) {
                $links[ 'namespaces' ][ 'practicegroup' ][ 'text' ] = wfMessage( 'practicegroups-practicegroup' )->text();
            } else {
                $titleNamespaceText = $title->getNsText() ? $title->getNsText() : 'Main';

                if( in_array( $titleNamespaceText, $wgPracticeGroupsOtherNotesNamespaces )
                    && $title->isSubpage() ) {
                    $title = PracticeGroups::getMainArticleTitle( $title );
                }
            }

            # If the title is a talk page, refer to the main article
            if( $title->isTalkPage() ) {
                $subjectPage = MediaWikiServices::getInstance()->getNamespaceInfo()->getSubjectPage( $title );

                $title = Title::newFromText( $subjectPage->getText(), $subjectPage->getNamespace() );
            }

            $titleNamespaceText = $title->getNsText() ? $title->getNsText() : 'Main';

            # If the current article is in an enabled namespace for PracticeGroup notes and is not a blacklisted title,
            # add the PracticeGroup article subpage to the list of related namespace pages and optionally create an action
            if( in_array( $titleNamespaceText, $wgPracticeGroupsNotesEnabledNamespaces )
                && !in_array( $title->getDBkey(), $wgPracticeGroupsNotesBlacklistTitles ) ) {

                $practiceGroupsUsers = PracticeGroups::getPracticeGroupsUsersForUser( $skinTemplate->getUser() );

                foreach( $practiceGroupsUsers as $practiceGroupsUser ) {
                    if( $practiceGroupsUser->isActive() ) {
                        $practiceGroup = $practiceGroupsUser->getPracticeGroup();

                        $practiceGroupArticleTitle = Title::newFromText( 'PracticeGroup:' . $practiceGroup->getDBKey() . '/' . $title->getText() );
                        $notesLinkText = wfMessage( 'practicegroups-practicegroupnotes-action', $practiceGroup->getShortName() )->text();

                        $links[ 'namespaces' ][ 'practicegroup:' . $practiceGroup->getDBKey() ] = [
                            'class' => '',
                            'text' => $notesLinkText,
                            'href' => $practiceGroupArticleTitle->getLinkURL(),
                            'exists' => true,
                            'primary' => true,
                            'context' => 'practicegroup'
                        ];

                        $practiceGroupTalkTitle = Title::newFromText( 'PracticeGroup_talk:' . $practiceGroup->getDBKey() . '/' . $title->getText() );
                        $talkLinkText = wfMessage( 'practicegroups-practicegrouptalk-action', $practiceGroup->getShortName() )->text();

                        $links[ 'namespaces' ][ 'practicegroup_talk:' . $practiceGroup->getDBKey() ] = [
                            'class' => '',
                            'text' => $talkLinkText,
                            'href' => $practiceGroupTalkTitle->getLinkURL(),
                            'exists' => true,
                            'primary' => true,
                            'context' => 'practicegrouptalk'
                        ];

                        if( $wgPracticeGroupsNotesAddAction ) {
                            # TODO could this 'action's be something like 'tabs' to get handed around to the skin to deal with it differently
                            $links[ 'actions' ][ 'practicegroup:' . $practiceGroup->getDBKey() ] = [
                                'class' => '',
                                'href' => $practiceGroupArticleTitle->getLinkURL(),
                                'text' => $notesLinkText
                            ];

                            $links[ 'actions' ][ 'practicegroup_talk:' . $practiceGroup->getDBKey() ] = [
                                'class' => '',
                                'href' => $practiceGroupTalkTitle->getLinkURL(),
                                'text' => $talkLinkText
                            ];
                        }
                    }
                }
            }
        }
    }
}