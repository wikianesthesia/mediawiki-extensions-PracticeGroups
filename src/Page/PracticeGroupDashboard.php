<?php

namespace PracticeGroups\Page;

use BootstrapUI\BootstrapUI;
use Html;
use MediaWiki\MediaWikiServices;
use PracticeGroups\Form\PracticeGroupFormEdit;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\PracticeGroups;
use OutputPage;
use RequestContext;
use Title;

class PracticeGroupDashboard {

    /**
     * @var PracticeGroup
     */
    private $practiceGroup;

    /**
     * @var OutputPage
     */
    private $output;

    public function __construct( PracticeGroup $practiceGroup,  &$out = null ) {
        if( !$practiceGroup ) {
            return;
        }

        $this->practiceGroup = $practiceGroup;

        if( $out ) {
            $this->output = $out;
        } else {
            $this->output = RequestContext::getMain()->getOutput();
        }
    }

    public function execute() {
        global $wgPracticeGroupsEmailMaxRate;

        $out = $this->getOutput();

        $title = $out->getTitle();

        if( !$this->practiceGroup || $title->isSubpage() ) {
            return;
        }

        $out->addModules( [
            'ext.practiceGroups.dashboard',
        ] );

        $out->addJsConfigVars( 'wgPracticeGroupsEmailMaxRate', $wgPracticeGroupsEmailMaxRate );

        $out->enableClientCache( false );
        $out->setPageTitle( (string) $this->practiceGroup );

        # Don't show the tabs if we're on a mediawiki action page
        if( !$out->getRequest()->getText( 'action' ) ) {
            static::setTabs( $out );

            PracticeGroups::wrapRenderShield( $out );
        }
    }

    protected function getOutput() {
        return $this->output;
    }

    protected function getPracticeGroup() {
        return $this->practiceGroup;
    }



    protected function setTabs() {
        $out = $this->getOutput();

        $practiceGroup = $this->getPracticeGroup();

        $myPracticeGroupsUser = $practiceGroup->getPracticeGroupsUserForUser( $out->getUser()->getId() );

        $navManager = BootstrapUI::getNavManager();

        $navManager->removeNavItem( 'article' );
        $navManager->removeNavItem( 'discussion' );

        # Home
        $navId = 'main';

        $tabContents = BootstrapUI::iconWidget( [ 'class' => 'fas fa-home' ] ) .
            Html::rawElement( 'span', [
                'class' => 'nav-label'
            ], wfMessage( 'practicegroups-home' )->text() );

        $navManager->addNavItem( $navId, [], $tabContents );

        $navManager->addTabPane( $navId, [], Html::rawElement( 'div', [
                'class' => 'mw-content-ltr ve-init-mw-desktopArticleTarget-editableContent',
                'id' => 'mw-content-text'
            ],
            $out->getHTML() )
        );

        # Articles
        $navId = 'articles';

        $tabContents = BootstrapUI::iconWidget( [ 'class' => 'fas fa-book-open' ] ) .
            Html::rawElement( 'span', [
                'class' => 'nav-label'
            ], wfMessage( 'practicegroups-articles' )->text() );

        $navManager->addNavItem( $navId, [], $tabContents );

        $navManager->addTabPane( $navId, [], static::getTabHtml( $navId ) );

        # Members
        $navId = 'members';

        $tabContents = BootstrapUI::iconWidget( [ 'class' => 'fas fa-users' ] ) .
            Html::rawElement( 'span', [
                'class' => 'nav-label'
            ], wfMessage( 'practicegroups-members' )->text() );

        $navManager->addNavItem( $navId, [], $tabContents );

        $navManager->addTabPane( $navId, [], static::getTabHtml( $navId ) );

        if( $myPracticeGroupsUser && $myPracticeGroupsUser->isActive() ) {
            # Settings
            $navId = 'settings';

            $tabContents = BootstrapUI::iconWidget( [ 'class' => 'fas fa-cog' ] ) .
                Html::rawElement( 'span', [
                    'class' => 'nav-label'
                ], wfMessage( 'practicegroups-settings' )->text() );

            $navManager->addNavItem( $navId, [], $tabContents );

            $navManager->addTabPane( $navId, [], static::getTabHtml( $navId ) );
        }

        $navManager->positionNavItem( 'menu', 'last' );

        $out->clearHTML();
    }



    /**
     * @param string $tabId
     * @return string
     */
    protected function getTabHtml( string $tabId ): string {
        if( $tabId == 'articles' ) {
            return static::getTabArticlesHtml();
        } elseif( $tabId == 'members' ) {
            return static::getTabMembersHtml();
        } elseif( $tabId == 'settings' ) {
            return static::getTabSettingsHtml();
        }

        return $tabId;
    }



    /**
     * @return string
     */
    protected function getTabArticlesHtml(): string {
        $tabArticlesHtml = '';

        $out = $this->getOutput();

        $practiceGroup = $this->getPracticeGroup();

        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

        $articles = $practiceGroup->getArticles();

        $tabArticlesHtml .= Html::openElement( 'table', [
            'class' => 'table table-borderless table-sm mb-0'
        ] );

        $tabArticlesHtml .= Html::openElement( 'tr' );

        $tabArticlesHtml .= Html::openElement( 'td', [
            'class' => 'align-middle'
        ] );

        $tabArticlesHtml .= Html::rawElement( 'h4', [],
            wfMessage( 'practicegroups-practicegroup-articles-search-label' )->text() );

        $tabArticlesHtml .= Html::closeElement( 'td' );

        $tabArticlesHtml .= Html::openElement( 'td', [
            'class' => 'align-middle practicegroups-table-buttons-1'
        ] );

        $tabArticlesHtml .= BootstrapUI::buttonWidget( [
            'class' => 'bs-ui-buttonHideLabelMobile',
            'id' => 'practicegroup-createarticle-button',
            'icon' => 'fas fa-plus',
            'label' => wfMessage( 'practicegroups-practicegroup-articles-create-button' )->text()
        ] );

        $tabArticlesHtml .= Html::closeElement( 'td' );

        $tabArticlesHtml .= Html::closeElement( 'tr' );

        $tabArticlesHtml .= Html::closeElement( 'table' );

        if( empty( $articles ) ) {
            $tabArticlesHtml .= wfMessage( 'practicegroups-practicegroup-noarticles' )->text();
        } else {
            $tabArticlesHtml .= Html::openElement( 'div', [
                'class' => 'table-responsive mt-2'
            ] );

            $tabArticlesHtml .= Html::openElement( 'table', [
                'class' => 'table table-sm',
                'id' => 'table-articles'
            ] );

            $thAttribs = [
                'scope' => 'col'
            ];

            $tabArticlesHtml .= Html::openElement( 'thead' );

            $tabArticlesHtml .= Html::rawElement( 'tr', [],
                Html::rawElement('th', $thAttribs, wfMessage( 'practicegroups-practicegroup-practicegrouparticle' )->text() )
                // . Html::rawElement('th', $thAttribs, '' )
            );

            $tabArticlesHtml .= Html::closeElement( 'thead' );

            $tabArticlesHtml .= Html::openElement( 'tbody' );

            $tdAttribs = [
                'class' => 'align-middle'
            ];

            $buttonsAttribs = [
                'class' => 'align-middle practicegroups-table-buttons-1'
            ];

            foreach( $articles as $article ) {
                $titleHtml = '';
                $buttonsHtml = '';

                $articleTitleText = PracticeGroups::getMainArticleText( $article );

                $titleHtml .= $linkRenderer->makeLink( $article, $articleTitleText );

                /*
                $buttonsHtml .= BootstrapUI::buttonWidget( [
                    'buttonStyle' => BootstrapUI::BUTTON_STYLE_OUTLINE_SECONDARY,
                    'class' => 'bs-ui-buttonHideLabelMobile',
                    'href' => $mainArticleTitle->getLinkURL(),
                    'icon' => BootstrapUI::iconWidget( [ 'class' => 'fas fa-link' ] ),
                    'label' => wfMessage( 'practicegroups-practicegroup-viewpublicarticle' )->text()
                ] );
                */

                $tabArticlesHtml .= Html::openElement( 'tr' );

                $tabArticlesHtml .= Html::rawElement( 'td', $tdAttribs, $titleHtml );
                //$tabArticlesHtml .= Html::rawElement( 'td', $buttonsAttribs, $buttonsHtml );

                $tabArticlesHtml .= Html::closeElement( 'tr' );
            }

            $tabArticlesHtml .= Html::closeElement( 'thead' );

            $tabArticlesHtml .= Html::closeElement( 'table' );
            $tabArticlesHtml .= Html::closeElement( 'div' );
        }

        return $tabArticlesHtml;
    }



    /**
     * @return string
     */
    protected function getTabMembersHtml(): string {
        $tabMembersHtml = '';

        $out = $this->getOutput();

        $practiceGroup = $this->getPracticeGroup();

        $myPracticeGroupsUser = $practiceGroup->getPracticeGroupsUserForUser( $out->getUser()->getId() );
        $myPracticeGroupsUserAdmin = $myPracticeGroupsUser ? $myPracticeGroupsUser->isAdmin() : false;

        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

        $thAttribs = [
            'scope' => 'col'
        ];

        if( $myPracticeGroupsUser && $myPracticeGroupsUser->canAddUser() ) {
            $requestedPracticeGroupsUsers = $practiceGroup->getRequestedPracticeGroupsUsers();

            $requestedPracticeGroupsUsersHtml = '';

            if( !empty( $requestedPracticeGroupsUsers ) ) {
                $requestedPracticeGroupsUsersHtml = Html::element( 'h4', [
                    'class' => 'mt-1'
                ], wfMessage( 'practicegroups-pendingrequests' )->text() );

                $requestedPracticeGroupsUsersHtml .= Html::openElement( 'div', [
                    'class' => 'table-responsive'
                ] );

                $requestedPracticeGroupsUsersHtml .= Html::openElement( 'table', [
                    'class' => 'table'
                ] );

                $requestedPracticeGroupsUsersHtml .= Html::openElement( 'tr' );
                $requestedPracticeGroupsUsersHtml .= Html::rawElement( 'th', $thAttribs, wfMessage( 'practicegroups-name' )->text() );
                $requestedPracticeGroupsUsersHtml .= Html::rawElement( 'th', $thAttribs, '&nbsp;' );
                $requestedPracticeGroupsUsersHtml .= Html::closeElement( 'tr' );

                foreach( $requestedPracticeGroupsUsers as $practiceGroupsUser ) {
                    $nameHtml = '';

                    if( $practiceGroupsUser->getUserId() ) {
                        $practiceGroupsUserMWUser = $practiceGroupsUser->getUser();

                        $nameHtml .= $linkRenderer->makeLink( $practiceGroupsUserMWUser->getUserPage(), $practiceGroupsUserMWUser->getRealName() );
                    } elseif( $practiceGroupsUser->getAffiliatedEmail() ) {
                        $nameHtml .= $practiceGroupsUser->getAffiliatedEmail();
                    }

                    $statusText = '';
                    $buttons = '';

                    $requestedPracticeGroupsUsersHtml .= Html::openElement( 'tr' );

                    if( $practiceGroup->canJoinByAffiliatedEmail()
                        && $practiceGroupsUser->getAffiliatedEmail() ) {
                        # Awaiting email confirmation
                        $statusText = wfMessage( 'practicegroups-practicegroup-status-awaitingemailverification' )->text();
                    } else {
                        $requestReason = $practiceGroupsUser->getRequestReason();

                        if( $requestReason ) {
                            $statusText = wfMessage( 'practicegroups-practicegroup-status-requestreason', $requestReason )->escaped();
                        } else {
                            $statusText = wfMessage( 'practicegroups-practicegroup-status-awaitingapproval' )->text();
                        }
                    }

                    $buttons .= BootstrapUI::buttonWidget( [
                        'class' => 'bs-ui-buttonHideLabelMobile',
                        'href' => Title::newFromText( 'Special:EmailUser' )->getLinkURL() . '?wpTarget=' . $practiceGroupsUserMWUser->getName(),
                        'icon' => 'fas fa-envelope fa-fw',
                        'label' => wfmessage( 'practicegroups-practicegroup-sendemail-button' )->text()
                    ] );

                    $buttons .= PracticeGroups::getMembershipButtonHtml( 'approverequest', $practiceGroupsUser->getId(), $practiceGroup->getId() );
                    $buttons .= PracticeGroups::getMembershipButtonHtml( 'rejectrequest', $practiceGroupsUser->getId(), $practiceGroup->getId() );

                    if( $statusText ) {
                        $nameHtml .= '<br />' . Html::rawElement( 'i', [], $statusText );
                    }

                    $nameAttribs = [
                        'class' => 'align-middle'
                    ];

                    if( !$buttons ) {
                        $nameAttribs[ 'colspan' ] = 2;
                    }

                    $requestedPracticeGroupsUsersHtml .= Html::rawElement( 'td', $nameAttribs, $nameHtml );

                    if( $buttons ) {
                        $buttons = BootstrapUI::buttonGroupWidget( [], $buttons );

                        $buttonsAttribs = [
                            'class' => 'align-middle practicegroups-table-buttons-2'
                        ];

                        $requestedPracticeGroupsUsersHtml .= Html::rawElement( 'td', $buttonsAttribs, $buttons );
                    }

                    $requestedPracticeGroupsUsersHtml .= Html::closeElement( 'tr' );
                }

                $requestedPracticeGroupsUsersHtml .= Html::closeElement( 'table' );
                $requestedPracticeGroupsUsersHtml .= Html::closeElement( 'div' );
            }

            $out->addModules( 'mediawiki.userSuggest' );

            $requestedPracticeGroupsUsersHtml .= Html::element( 'h4', [], wfMessage( 'practicegroups-practicegroup-inviteuser-label' )->text() );

            $requestedPracticeGroupsUsersHtml .= Html::openElement( 'div', [
                'class' => 'input-group mb-3'
            ] );

            $requestedPracticeGroupsUsersHtml .= Html::rawElement( 'input', [
                'type' => 'search',
                'id' => 'practicegroups-inviteuser-search',
                'class' => 'form-control mw-autocomplete-user',
                'placeholder' => wfMessage( 'practicegroups-practicegroup-inviteuser-placeholder' )->text(),
                'autocomplete' => 'off'
            ] );

            $requestedPracticeGroupsUsersHtml .= Html::openElement( 'div', [
                'class' => 'input-group-append'
            ] );

            $requestedPracticeGroupsUsersHtml .= PracticeGroups::getMembershipButtonHtml( 'inviteuser', 0, $practiceGroup->getId() );

            $requestedPracticeGroupsUsersHtml .= Html::closeElement( 'div' );
            $requestedPracticeGroupsUsersHtml .= Html::closeElement( 'div' );

            $requestedPracticeGroupsUsersHtml .= BootstrapUI::buttonWidget( [
                'id' => 'practicegroup-massinvite-button',
                'icon' => 'fas fa-user-friends',
                'label' => wfMessage( 'practicegroups-practicegroup-massinvite-button' )->text()
            ] );

            $requestedPracticeGroupsUsersHtml .= '<br/>';

            $tabMembersHtml .= $requestedPracticeGroupsUsersHtml;

            $invitedPracticeGroupsUsers = $practiceGroup->getInvitedPracticeGroupsUsers();

            if( !empty( $invitedPracticeGroupsUsers ) ) {
                $invitedPracticeGroupsUsersHtml = '';

                $invitedPracticeGroupsUsersHtml .= BootstrapUI::buttonWidget( [
                    'class' => 'collapsed',
                    'data-toggle' => 'collapse',
                    'href' => '#collapsePendingInvitations',
                    'icon' => 'fa fa-chevron-right',
                    'label' => wfMessage( 'practicegroups-members-pending-invitations', count( $invitedPracticeGroupsUsers ) )->text(),
                    'aria-expanded' => 'false',
                    'aria-controls' => 'collapsePendingInvitations'
                ] );

                $invitedPracticeGroupsUsersHtml .= Html::openElement( 'div', [
                    'class' => 'collapse mt-3',
                    'id' => 'collapsePendingInvitations'
                ] );

                $invitedPracticeGroupsUsersHtml .= Html::openElement( 'div', [
                    'class' => 'table-responsive mb-3'
                ] );

                $invitedPracticeGroupsUsersHtml .= Html::openElement( 'table', [
                    'class' => 'table table-sm',
                    'id' => 'table-pendinginvitations'
                ] );

                $invitedPracticeGroupsUsersHtml .= Html::openElement( 'thead' );
                $invitedPracticeGroupsUsersHtml .= Html::openElement( 'tr' );

                $invitedPracticeGroupsUsersHtml .= Html::rawElement('th', $thAttribs, wfMessage( 'practicegroups-name' )->text() );
                $invitedPracticeGroupsUsersHtml .= Html::rawElement('th', $thAttribs, '&nbsp;' );

                $invitedPracticeGroupsUsersHtml .= Html::closeElement( 'tr' );
                $invitedPracticeGroupsUsersHtml .= Html::closeElement( 'thead' );

                $invitedPracticeGroupsUsersHtml .= Html::openElement( 'tbody' );

                $tdAttribs = [
                    'class' => 'align-middle'
                ];

                $tdButtonsAttribs = $tdAttribs;
                $tdButtonsAttribs[ 'class' ] .= ' practicegroups-table-buttons-2';

                foreach( $invitedPracticeGroupsUsers as $practiceGroupsUser ) {
                    $nameHtml = '';

                    if( $practiceGroupsUser->getUserId() ) {
                        $practiceGroupsUserMWUser = $practiceGroupsUser->getUser();

                        $nameHtml .= $linkRenderer->makeLink( $practiceGroupsUserMWUser->getUserPage(), $practiceGroupsUserMWUser->getRealName() );
                    } elseif( $practiceGroupsUser->getAffiliatedEmail() ) {
                        $nameHtml .= $practiceGroupsUser->getAffiliatedEmail();
                    }

                    $invitedPracticeGroupsUsersHtml .= Html::openElement( 'tr' );

                    $invitingUser = $practiceGroupsUser->getApprovedByUser();

                    # Only admins and the inviting user should be able to see/cancel pending invitations
                    if( !$myPracticeGroupsUserAdmin && ( !$invitingUser || $invitingUser->getId() != $out->getUser()->getId() ) ) {
                        // TODO fix this. Anything skipped here isn't decremented from the count shown by the button so it's confusing
                        //continue;
                    }

                    if( $invitingUser ) {
                        $invitingUserLinkText = $invitingUser->getId() == $out->getUser()->getId() ? wfMessage( 'practicegroups-you' )->text() : $invitingUser->getRealName();

                        $statusText = wfMessage( 'practicegroups-practicegroup-status-invitationpendingfromuser',
                            $linkRenderer->makeLink( $invitingUser->getUserPage(), $invitingUserLinkText )
                        )->text();
                    } else {
                        $statusText = wfMessage( 'practicegroups-practicegroup-status-invitationpending' )->text();
                    }

                    if( $statusText ) {
                        $nameHtml .= '<br />' . Html::rawElement( 'i', [], $statusText );
                    }

                    $nameAttribs = [
                        'class' => 'align-middle'
                    ];

                    $invitedPracticeGroupsUsersHtml .= Html::rawElement( 'td', $nameAttribs, $nameHtml );

                    $buttons = BootstrapUI::buttonGroupWidget( [],
                        PracticeGroups::getMembershipButtonHtml( 'cancelinvitation', $practiceGroupsUser->getId(), $practiceGroup->getId() )
                    );

                    $buttonsAttribs = [
                        'class' => 'align-middle practicegroups-table-buttons-2'
                    ];

                    $invitedPracticeGroupsUsersHtml .= Html::rawElement( 'td', $buttonsAttribs, $buttons );

                    $invitedPracticeGroupsUsersHtml .= Html::closeElement( 'tr' );
                }

                $invitedPracticeGroupsUsersHtml .= Html::openElement( 'tbody' );

                $invitedPracticeGroupsUsersHtml .= Html::closeElement( 'table' );
                $invitedPracticeGroupsUsersHtml .= Html::closeElement( 'div' );

                $invitedPracticeGroupsUsersHtml .= Html::closeElement( 'div' );

                $invitedPracticeGroupsUsersHtml .= Html::rawElement( 'hr' );

                $tabMembersHtml .= $invitedPracticeGroupsUsersHtml;
            }
        }

        $activePracticeGroupsUsers = $practiceGroup->getActivePracticeGroupsUsers();

        if( !empty( $activePracticeGroupsUsers ) ) {
            $activePracticeGroupsUsersHtml = '';

            $activePracticeGroupsUsersHtml .= Html::element( 'h4', [], wfMessage( 'practicegroups-members-section', count( $activePracticeGroupsUsers ) )->text() );

            $activePracticeGroupsUsersHtml .= Html::openElement( 'div', [
                'class' => 'table-responsive mb-3'
            ] );

            $activePracticeGroupsUsersHtml .= Html::openElement( 'table', [
                'class' => 'table table-sm',
                'id' => 'table-activemembers'
            ] );

            $activePracticeGroupsUsersHtml .= Html::openElement( 'thead' );
            $activePracticeGroupsUsersHtml .= Html::openElement( 'tr' );

            $activePracticeGroupsUsersHtml .= Html::rawElement('th', $thAttribs, wfMessage( 'practicegroups-name' )->text() );
            $activePracticeGroupsUsersHtml .= Html::rawElement('th', $thAttribs, '&nbsp;' );

            $activePracticeGroupsUsersHtml .= Html::closeElement( 'tr' );
            $activePracticeGroupsUsersHtml .= Html::closeElement( 'thead' );

            $activePracticeGroupsUsersHtml .= Html::openElement( 'tbody' );

            $tdAttribs = [
                'class' => 'align-middle'
            ];

            $tdButtonsAttribs = $tdAttribs;
            $tdButtonsAttribs[ 'class' ] .= ' practicegroups-table-buttons-2';

            foreach( $activePracticeGroupsUsers as $practiceGroupsUser ) {
                $practiceGroupsUserMWUser = $practiceGroupsUser->getUser();

                $nameHtml = $linkRenderer->makeLink( $practiceGroupsUserMWUser->getUserPage(), $practiceGroupsUserMWUser->getRealName() );

                $statusText = '';
                $buttons = '';

                $activePracticeGroupsUsersHtml .= Html::openElement( 'tr' );

                if( $practiceGroupsUser->isAdmin() ) {
                    $statusText = wfMessage( 'practicegroups-practicegroup-status-admin' )->text();
                }

                $buttons .= BootstrapUI::buttonWidget( [
                    'class' => 'bs-ui-buttonHideLabelMobile',
                    'href' => Title::newFromText( 'Special:EmailUser' )->getLinkURL() . '?wpTarget=' . $practiceGroupsUserMWUser->getName(),
                    'icon' => 'fas fa-envelope fa-fw',
                    'label' => wfmessage( 'practicegroups-practicegroup-sendemail-button' )->text()
                ] );

                if( $myPracticeGroupsUserAdmin ) {
                    if( !$practiceGroupsUser->isAdmin() ) {
                        $buttons .= PracticeGroups::getMembershipButtonHtml( 'promoteadmin', $practiceGroupsUser->getId(), $practiceGroup->getId() );
                    } else {
                        $buttons .= PracticeGroups::getMembershipButtonHtml( 'demoteadmin', $practiceGroupsUser->getId(), $practiceGroup->getId() );
                    }

                    $buttons .= PracticeGroups::getMembershipButtonHtml( 'removeuser', $practiceGroupsUser->getId(), $practiceGroup->getId() );
                }

                if( $statusText ) {
                    $nameHtml .= ' ' . Html::element( 'i', [], '(' . $statusText . ')' );
                }

                $activePracticeGroupsUsersHtml .= Html::rawElement( 'td', $tdAttribs, $nameHtml );

                $buttons = BootstrapUI::buttonGroupWidget( [], $buttons );

                $activePracticeGroupsUsersHtml .= Html::rawElement( 'td', $tdButtonsAttribs, $buttons );

                $activePracticeGroupsUsersHtml .= Html::closeElement( 'tr' );
            }

            $activePracticeGroupsUsersHtml .= Html::openElement( 'tbody' );

            $activePracticeGroupsUsersHtml .= Html::closeElement( 'table' );
            $activePracticeGroupsUsersHtml .= Html::closeElement( 'div' );

            $tabMembersHtml .= $activePracticeGroupsUsersHtml;
        }

        return $tabMembersHtml;
    }



    /**
     * @return string
     */
    protected function getTabSettingsHtml(): string {
        $tabSettingsHtml = '';

        $tabSettingsHtml .= PracticeGroupFormEdit::getHtml( $this->getPracticeGroup() );

        return $tabSettingsHtml;
    }
}