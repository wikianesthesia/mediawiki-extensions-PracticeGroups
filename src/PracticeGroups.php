<?php

namespace PracticeGroups;

use BootstrapUI\BootstrapUI;
use Html;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use OutputPage;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\DatabaseClass\PracticeGroupsPageSetting;
use PracticeGroups\DatabaseClass\PracticeGroupsUser;
use Psr\Log\LoggerInterface;
use RequestContext;
use Status;
use Title;
use User;

class PracticeGroups {

    protected const EXTENSION_NAME = 'PracticeGroups';

    protected const NAMESPACES = [
        NS_PRACTICEGROUP,
        NS_PRACTICEGROUP_TALK,
    ];

    protected const MEMBERSHIP_BUTTON_TYPES = [
        'acceptinvitation' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_SUCCESS,
            'icon' => 'fas fa-check'
        ],
        'approverequest' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_SUCCESS,
            'icon' => 'fas fa-check'
        ],
        'cancelinvitation' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_DANGER,
            'icon' => 'fas fa-ban'
        ],
        'cancelrequest' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_DANGER,
            'icon' => 'fas fa-ban'
        ],
        'declineinvitation' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_DANGER,
            'icon' => 'fas fa-times'
        ],
        'demoteadmin' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_WARNING,
            'icon' => 'fas fa-level-down-alt'
        ],
        'inviteuser' => [
            'icon' => 'fas fa-user-plus'
        ],
        'join' => [
            'icon' => 'fas fa-sign-in-alt'
        ],
        'leave' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_DANGER,
            'icon' => 'fas fa-trash-alt'
        ],
        'promoteadmin' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_WARNING,
            'icon' => 'fas fa-level-up-alt'
        ],
        'rejectrequest' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_DANGER,
            'icon' => 'fas fa-times'
        ],
        'removeuser' => [
            'buttonStyle' => BootstrapUI::BUTTON_STYLE_DANGER,
            'icon' => 'fas fa-user-times'
        ],
        'resendemail' => [
            'icon' => 'fas fa-paper-plane'
        ]
    ];

    protected static $allowedPracticeGroups;
    protected static $membershipButtonCount = [];
    protected static $myPracticeGroupsUsers;

    public static function canTitleHavePracticeGroupArticle( $title ) {
        global $wgPracticeGroupsNotesBlacklistTitles, $wgPracticeGroupsNotesEnabledNamespaces;

        if( !$title ) {
            return false;
        }

        if( !$title instanceof Title ) {
            $title = Title::newFromText( $title );
        }

        $titleNamespaceText = $title->getNsText() ? $title->getNsText() : 'Main';

        if( !in_array( $titleNamespaceText, $wgPracticeGroupsNotesEnabledNamespaces )
            || in_array( $title->getDBkey(), $wgPracticeGroupsNotesBlacklistTitles ) ) {
            return false;
        }

        return true;
    }

    /**
     * Returns all practice groups that the user can read
     *
     * @return false|PracticeGroup[]
     */
    public static function getAllAllowedPracticeGroups() {
        if( !isset( static::$allowedPracticeGroups ) ) {
            $user = RequestContext::getMain()->getUser();

            if( static::isUserPracticeGroupSysop( $user ) ) {
                $allowedPracticeGroups = PracticeGroup::getAll();
            } else {
                $allowedPracticeGroups = static::getAllPublicPracticeGroups();

                if( $user->isRegistered() ) {
                    $practiceGroupsUsers = static::getPracticeGroupsUsersForUser( $user );;

                    foreach( $practiceGroupsUsers as $practiceGroupsUser ) {
                        $practiceGroup = $practiceGroupsUser->getPracticeGroup();

                        if( !array_key_exists( $practiceGroup->getId(), $allowedPracticeGroups ) &&
                            $practiceGroupsUser->isActive() ) {
                            $allowedPracticeGroups[ $practiceGroup->getId() ] = $practiceGroup;
                        }
                    }
                }
            }

            static::$allowedPracticeGroups = $allowedPracticeGroups;
        }

        return static::$allowedPracticeGroups;
    }

    /**
     * @return false|PracticeGroup[]
     */
    public static function getAllPublicPracticeGroups() {
        return PracticeGroup::getAll( [ 'view_by_public' => 1 ] );
    }

    /**
     * @param int $pageId
     * @return false|int
     */
    public static function getEffectivePrivacyForPage( int $pageId ) {
        if( !$pageId ) {
            return false;
        }

        $title = Title::newFromID( $pageId );
        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        if( !$title->exists() || !$practiceGroup ) {
            return false;
        }

        $privacy = PracticeGroupsPageSetting::PRIVACY_INHERIT;

        while( $privacy == PracticeGroupsPageSetting::PRIVACY_INHERIT ) {
            $practiceGroupPageSettings = PracticeGroupsPageSetting::getCurrentForPage( $title->getArticleID() );

            if( $practiceGroupPageSettings && $practiceGroupPageSettings->getPrivacy() != PracticeGroupsPageSetting::PRIVACY_INHERIT) {
                $privacy = $practiceGroupPageSettings->getPrivacy();
            } elseif( $title->isSubpage() ) {
                $title = $title->getBaseTitle();
            } else {
                if( $practiceGroup->canViewByPublic() ) {
                    $privacy = PracticeGroupsPageSetting::PRIVACY_PUBLIC;
                } else {
                    $privacy = PracticeGroupsPageSetting::PRIVACY_PRIVATE;
                }
            }
        }

        return $privacy;
    }

    /**
     * @return string
     */
    public static function getExtensionName(): string {
        return self::EXTENSION_NAME;
    }

    /**
     * @param PracticeGroup $practiceGroup
     * @param Title $mainArticleTitle
     * @return Title[]
     */
    public static function getLinkedPracticeGroupArticleTitles( PracticeGroup $practiceGroup, Title $mainArticleTitle ): array {
        $linkedPracticeGroupTitles = [];

        if( $mainArticleTitle->exists() ) {
            $relatedTitles = array_merge( [ $mainArticleTitle ], $mainArticleTitle->getRedirectsHere( NS_MAIN ) );

            foreach( $relatedTitles as $relatedTitle ) {
                $linkedPracticeGroupTitle = Title::newFromText(
                    'PracticeGroup:' . $practiceGroup->getDBKey() . '/' . $relatedTitle->getDBkey() );

                if( $linkedPracticeGroupTitle->exists() ) {
                    $linkedPracticeGroupTitles[] = $linkedPracticeGroupTitle;
                }
            }
        }

        return $linkedPracticeGroupTitles;
    }

    /**
     * @return LoggerInterface
     */
    public static function getLogger(): LoggerInterface {
        return LoggerFactory::getInstance( static::getExtensionName() );
    }

    /**
     * This function does no validation on the practicegroup or user ids.
     *
     * @param string $buttonType
     * @param int $practiceGroupsUserId
     * @param int $practiceGroupId
     * @param int $userId
     * @return String
     */
    public static function getMembershipButtonHtml( string $buttonType, int $practiceGroupsUserId, int $practiceGroupId ): String {
        $html = '';

        if( !isset( static::MEMBERSHIP_BUTTON_TYPES[ $buttonType ] ) ) {
            return $html;
        }

        if( !isset( static::$membershipButtonCount[ $buttonType ] ) ) {
            static::$membershipButtonCount[ $buttonType ] = 0;
        }

        $buttonCount = ++static::$membershipButtonCount[ $buttonType ];

        $buttonTypeData = static::MEMBERSHIP_BUTTON_TYPES[ $buttonType ];

        $elementBase = 'practicegroup-' . $buttonType;
        $buttonClass = $elementBase . '-button';

        $elementName = 'practicegroupsuser_id';
        $html .= Html::openElement( 'input', [
            'name' => $elementName,
            'value' => $practiceGroupsUserId,
            'id' => $elementBase . '-' . $elementName . '-' . $buttonCount,
            'type' => 'hidden'
        ] );

        $elementName = 'practicegroup_id';
        $html .= Html::openElement( 'input', [
            'name' => $elementName,
            'value' => $practiceGroupId,
            'id' => $elementBase . '-' . $elementName . '-' . $buttonCount,
            'type' => 'hidden'
        ] );

        $buttonConfig = [
            'id' => $buttonClass . '-' . $buttonCount,
            'buttonStyle' => isset( $buttonTypeData[ 'buttonStyle' ] ) ? $buttonTypeData[ 'buttonStyle' ] : BootstrapUI::DEFAULT_BUTTON_STYLE,
            'class' => 'bs-ui-buttonHideLabelMobile ' . $buttonClass,
            'icon' => isset( $buttonTypeData[ 'icon' ] ) ? $buttonTypeData[ 'icon' ] . ' fa-fw' : '',
            'label' => wfMessage( 'practicegroups-' . $buttonClass )->text()
        ];

        $html .= BootstrapUI::buttonWidget( $buttonConfig );

        return $html;
    }

    public static function getMembershipPolicyDetailsHtml( PracticeGroup $practiceGroup ): String {
        $html = '';

        if( !$practiceGroup ) {
            return $html;
        }

        $msgPrefix = 'practicegroups-membershippolicy-details-';

        $publicRights = [];

        if( $practiceGroup->canViewByPublic() ) {
            $publicRights[] = 'view';
        }

        if( $practiceGroup->canJoinByPublic() ) {
            $publicRights[] = 'join';
        }

        if( $practiceGroup->canJoinByRequest() ) {
            $publicRights[] = 'request';
        }

        if( $practiceGroup->canJoinByAffiliatedEmail() ) {
            $publicRights[] = 'affiliatedemail';
        }

        if( empty( $publicRights ) ) {
            $html .= wfMessage( $msgPrefix . 'invitationonly' );

            return $html;
        }

        $html .= wfMessage( $msgPrefix . 'anyusercan' );

        $html .= Html::openElement( 'ul', [
            'class' => 'mb-0'
        ] );


        foreach( $publicRights as $rightKey ) {
            $html .= Html::rawElement( 'li', [], wfMessage( $msgPrefix . $rightKey )->text() );
        }

        $html .= Html::closeElement( 'ul' );

        return $html;
    }

    /**
     * @param Title|string $practiceGroupTitle
     * @return null|string
     */
    public static function getMainArticleText( $practiceGroupTitle ) {
        if( !$practiceGroupTitle ) {
            return false;
        }

        if( !$practiceGroupTitle instanceof Title ) {
            $practiceGroupTitle = Title::newFromText( $practiceGroupTitle );
        }

        return preg_replace( '/' . preg_quote( $practiceGroupTitle->getRootText() ) . '\/?/', '', $practiceGroupTitle->getText() );
    }

    /**
     * @param Title|string $practiceGroupArticleTitle
     * @return null|Title
     */
    public static function getMainArticleTitle( $practiceGroupArticleTitle ) {
        return Title::newFromText( static::getMainArticleText( $practiceGroupArticleTitle ) );
    }

    /**
     * @param int $pagePrivacy
     * @return string
     */
    public static function getPagePrivacyText( int $pagePrivacy ): string {
        if( !in_array( $pagePrivacy, PracticeGroupsPageSetting::VALID_PRIVACY ) ) {
            return '';
        }

        return wfMessage( $pagePrivacy == PracticeGroupsPageSetting::PRIVACY_PRIVATE ?
            'practicegroups-privacy-private' :
            ( $pagePrivacy == PracticeGroupsPageSetting::PRIVACY_PUBLIC ?
                'practicegroups-privacy-public' : 'practicegroups-privacy-inherit'
            )
        )->text();
    }

    /**
     * @param $titleText
     * @param PracticeGroup|null $practiceGroup
     * @return string
     */
    public static function getPracticeGroupArticleDisplayTitle( $titleText, $practiceGroup = null ): string {
        if( !$titleText ) {
            return '';
        }

        if( !$practiceGroup ) {
            # $titleText is the prefixedText of an article

            $practiceGroupArticleTitleRegexp = '/PracticeGroup:(?<dbkey>[^\/]+)\/(?<title>.*)/';

            if( !preg_match( $practiceGroupArticleTitleRegexp, $titleText, $matches ) ) {
                return '';
            }

            $titleText = $matches[ 'title' ];

            $practiceGroup = PracticeGroup::getFromDBKey( $matches[ 'dbkey' ] );

            if( !$practiceGroup ) {
                return '';
            }
        }

        return wfMessage( 'practicegroups-articletitle', $titleText, $practiceGroup->getShortName() )->text();
    }

    public static function getPracticeGroupBadge( PracticeGroup $practiceGroup, string $class = 'practicegroups-searchresults-badge' ): string {
        $badgeAttribs = [
            'class' => $class,
        ];

        $badgeStyle = '';

        if( $practiceGroup->getPrimaryColor() ) {
            $badgeStyle .= 'background-color: ' . $practiceGroup->getPrimaryColor() . ';';
        }

        if( $practiceGroup->getSecondaryColor() ) {
            $badgeStyle .= 'color: ' . $practiceGroup->getSecondaryColor() . ';';
        }

        if( $badgeStyle ) {
            $badgeAttribs[ 'style' ] = $badgeStyle;
        }

        return BootstrapUI::badgeWidget( $badgeAttribs, $practiceGroup->getShortName() );
    }

    public static function getPracticeGroupFromTitle( $title ) {
        if( !static::isTitlePracticeGroupArticle( $title ) ) {
            return false;
        }

        if( !$title instanceof Title ) {
            $title = Title::newFromText( $title );
        }

        $titleText = $title->getText();

        if( preg_match('/([\w-]+)\/?/', $titleText, $matches ) ) {
            if( count( $matches ) > 1 ) {
                return PracticeGroup::getFromDBKey( $matches[ 1 ] );
            }
        }

        return false;
    }

    public static function getPracticeGroupsNamespaces(): array {
        return self::NAMESPACES;
    }

    public static function getPracticeGroupsUserForUser( PracticeGroup $practiceGroup, User $user = null ): ?PracticeGroupsUser {
        $user = $user ?? RequestContext::getMain()->getUser();

        if( !$user->isRegistered() ) {
            return null;
        }

        $isMyUser = $user->getId() === RequestContext::getMain()->getUser()->getId();

        if( $isMyUser ) {
            if( !isset( static::$myPracticeGroupsUsers ) ) {
                // If we're going to have to do a lookup anyway, might as well load and cache all the PracticeGroupsUsers
                static::getPracticeGroupsUsersForUser( $user );
            }

            return static::$myPracticeGroupsUsers[ $practiceGroup->getId() ] ?? null;
        }

        $practiceGroupsUser = PracticeGroupsUser::getAll( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => $user->getId()
        ] );

        if( $practiceGroupsUser ) {
            $practiceGroupsUser = array_shift( $practiceGroupsUser );
        } else {
            $practiceGroupsUser = null;
        }

        return $practiceGroupsUser;
    }

    /**
     * @param User $user
     * @return PracticeGroupsUser[]
     */
    public static function getPracticeGroupsUsersForUser( User $user = null ): array {
        $user = $user ?? RequestContext::getMain()->getUser();

        if( !$user->isRegistered() ) {
            return [];
        }

        $isMyUser = $user->getId() === RequestContext::getMain()->getUser()->getId();

        if( $isMyUser && isset( static::$myPracticeGroupsUsers ) ) {
            return static::$myPracticeGroupsUsers;
        }

        $queriedPracticeGroupsUsers = PracticeGroupsUser::getAll( [ 'user_id' => $user->getId() ] ) ?: [];
        $practiceGroupsUsers = [];
        foreach( $queriedPracticeGroupsUsers as $practiceGroupsUser ) {
            $practiceGroupsUsers[ $practiceGroupsUser->getPracticeGroupId() ] = $practiceGroupsUser;
        }

        if( $isMyUser ) {
            static::$myPracticeGroupsUsers = $practiceGroupsUsers;
        }

        return $practiceGroupsUsers;
    }

    public static function getUserIdForEmail( string $email ) {
        $clean_email = filter_var( $email,FILTER_SANITIZE_EMAIL );

        if( $email != $clean_email || !filter_var( $email,FILTER_VALIDATE_EMAIL ) ) {
            return null;
        }

        $db = wfGetDB( DB_REPLICA );

        $s = $db->selectRow(
            'user',
            [ 'user_id' ],
            [ 'user_email' => $email ],
            __METHOD__
        );

        if ( $s === false ) {
            $result = null;
        } else {
            $result = (int)$s->user_id;
        }

        return $result;
    }

    /**
     * Practice groups extension callback
     */
    public static function init() {
        global $wgNonincludableNamespaces;

        foreach( self::NAMESPACES as $namespace ) {
            $wgNonincludableNamespaces[] = $namespace;
        }
    }

    public static function isTitlePracticeGroupArticle( $title ): bool {
        if( !$title ) {
            return false;
        }

        if( !$title instanceof Title ) {
            $title = Title::newFromText( $title );
        }

        if( !in_array( $title->getNamespace(), self::getPracticeGroupsNamespaces() ) ) {
            return false;
        }

        return true;
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public static function isUserPracticeGroupSysop( User $user = null ): bool {
        $user = $user ?? RequestContext::getMain()->getUser();

        if( MediaWikiServices::getInstance()->getPermissionManager()->userHasRight(
                $user,
                'practicegroups-sysop'
            )
        ) {
            return true;
        }

        return false;
    }

    public static function purgeMyPracticeGroupsUsers() {
        static::$myPracticeGroupsUsers = null;
    }

    /**
     * @param string $search
     * @return false|Title[]
     */
    public static function searchPracticeGroupArticleTitles( string $search ) {
        if( strpos( $search, ':' ) !== false ) {
            return false;
        }

        $user = RequestContext::getMain()->getUser();

        if( !$user->isRegistered() ) {
            return false;
        }

        $myPracticeGroupsUsers = static::getPracticeGroupsUsersForUser( $user );

        if( !count( $myPracticeGroupsUsers ) ) {
            return false;
        }

        $titles = [];

        $searchEngine = MediaWikiServices::getInstance()->newSearchEngine();

        foreach( $myPracticeGroupsUsers as $practiceGroupsUser ) {
            if( $practiceGroupsUser->isActive() ) {
                $practiceGroup = $practiceGroupsUser->getPracticeGroup();

                if( !$practiceGroup ) {
                    continue;
                }

                $titles = array_merge( $titles, $searchEngine->extractTitles( $searchEngine->completionSearchWithVariants( $practiceGroup->getPrefixedDBKey() . '/' . $search ) ) );
            }
        }

        return $titles;
    }

    public static function setTabs() {
        global $wgPracticeGroupsOtherNotesNamespaces;

        $user = RequestContext::getMain()->getUser();
        $title = RequestContext::getMain()->getTitle();

        $mainArticleTitle = $title;

        if( $title->isTalkPage() ) {
            $mainArticleTitle = $title->getOtherPage();
        }

        if( self::isTitlePracticeGroupArticle( $mainArticleTitle )
            || in_array( $mainArticleTitle->getNsText(), $wgPracticeGroupsOtherNotesNamespaces ) ) {
            $mainArticleTitle = self::getMainArticleTitle( $mainArticleTitle );
        }

        if( !$user->isRegistered()
            || !self::canTitleHavePracticeGroupArticle( $mainArticleTitle )
            || ( self::isTitlePracticeGroupArticle( $title ) && !$title->isSubpage() ) ) {
            return;
        }

        $practiceGroupsUsers = static::getPracticeGroupsUsersForUser( $user );

        $practiceGroupNavItems = [];

        foreach( $practiceGroupsUsers as $practiceGroupsUser ) {
            if( !$practiceGroupsUser->isActive() ) {
                continue;
            }

            $practiceGroup = $practiceGroupsUser->getPracticeGroup();

            // Get all possible linked practice group titles (the main article as well as any redirects)
            $linkedPracticeGroupArticleTitles = static::getLinkedPracticeGroupArticleTitles( $practiceGroup, $mainArticleTitle );

            if( !count( $linkedPracticeGroupArticleTitles ) ) {
                $linkedPracticeGroupArticleTitles[] = Title::newFromText(
                    'PracticeGroup:' . $practiceGroup->getDBKey() . '/' . $mainArticleTitle->getDBkey()
                );
            }

            foreach( $linkedPracticeGroupArticleTitles as $linkedPracticeGroupArticleTitle ) {
                $practiceGroupNavItems[] = [
                    'practiceGroup' => $practiceGroup,
                    'title' => $linkedPracticeGroupArticleTitle
                ];
            }
        }

        if( empty( $practiceGroupNavItems ) ) {
            return;
        }

        $navManager = BootstrapUI::getNavManager();

        $navId = 'practicegroups';

        $navItemAttribs = [
            'active' => self::isTitlePracticeGroupArticle( $title ) && !$title->isTalkPage()
        ];

        $tabIcon = BootstrapUI::iconWidget( [ 'class' => 'fas fa-hospital-alt fa-fw' ] );

        if( count( $practiceGroupNavItems ) === 1 ) {
            $linkMessage = $practiceGroupNavItems[ 0 ][ 'title' ]->exists() ?
                'practicegroups-practicegrouparticle-action' :
                'practicegroups-practicegrouparticle-actioncreate';

            $navItemAttribs[ 'contents' ] = $tabIcon . Html::rawElement( 'span', [
                    'class' => 'nav-label'
                ], wfMessage( $linkMessage,
                    $practiceGroupNavItems[ 0 ][ 'practiceGroup' ]->getShortName() )->text() );

            $navItemAttribs[ 'href' ] = $practiceGroupNavItems[ 0 ][ 'title' ]->getLinkURL();

            $navManager->addNavItem( $navId, $navItemAttribs );
        } else {
            $navItemAttribs[ 'contents' ] = $tabIcon . Html::rawElement( 'span', [
                    'class' => 'nav-label'
                ], wfMessage( 'practicegroups-practicegrouparticles' )->text() );

            $navManager->addNavItem( $navId, $navItemAttribs );

            foreach( $practiceGroupNavItems as $practiceGroupNavItem ) {
                $mainArticleText = static::getMainArticleText( $practiceGroupNavItem[ 'title' ] );

                $itemText = $practiceGroupNavItem[ 'title' ]->exists() ?
                    $mainArticleText :
                    wfMessage( 'practicegroups-practicegrouparticle-actioncreatetitle', $mainArticleText )->text();

                $practiceGroupNavItemAttribs = [
                    'contents' => static::getPracticeGroupBadge( $practiceGroupNavItem[ 'practiceGroup' ] ) . $itemText,
                    'href' => $practiceGroupNavItem[ 'title' ]->getLinkURL()
                ];

                $navManager->addDropdownItem( $navId, $practiceGroupNavItemAttribs );
            }
        }

        $navManager->positionNavItem( 'discussion', 'last' );
        $navManager->positionNavItem( 'menu', 'last' );

        # Modify article tab
        $navId = 'article';

        $navItem = $navManager->getNavItem( $navId );

        if( self::isTitlePracticeGroupArticle( $title )
            || in_array( $title->getNsText(), $wgPracticeGroupsOtherNotesNamespaces ) ) {
            $navItem[ 'active' ] = false;
            $navItem[ 'href' ] = $mainArticleTitle->getLocalURL();
        }

        $articleTabMessage = $mainArticleTitle->exists() ? 'practicegroups-mainarticle' : 'practicegroups-mainarticlenew';

        $navItem[ 'contents' ] = BootstrapUI::iconWidget( [ 'class' => 'fas fa-file-alt' ] ) .
            Html::rawElement( 'span', [
                'class' => 'nav-label'
            ], wfMessage( $articleTabMessage )->text() );

        $navManager->addNavItem( $navId, $navItem );

        # If title is a practicegroup article, modify the article and discussion tabs
        if( self::isTitlePracticeGroupArticle( $title )
            || in_array( $title->getNsText(), $wgPracticeGroupsOtherNotesNamespaces ) ) {
            $navId = 'discussion';

            $navItem = $navManager->getNavItem( $navId );
            $navItem[ 'href' ] = $mainArticleTitle->getTalkPageIfDefined()->getLocalURL();

            $navManager->addNavItem( $navId, $navItem );
        }

        $navId = 'discussion';

        $navItem = $navManager->getNavItem( $navId );

        if( !isset( $navItem[ 'dropdownItems' ] ) ) {
            $navManager->addDropdownItem( $navId, [
                'href' => $navItem[ 'href' ]
            ], wfMessage( 'practicegroups-maintalk', )->text() );
        }

        foreach( $practiceGroupNavItems as $practiceGroupNavItem ) {
            if( $practiceGroupNavItem[ 'title' ]->exists() ) {
                $practiceGroupTalkTitle = $practiceGroupNavItem[ 'title' ]->getTalkPageIfDefined();

                $discussionNavItemAttribs = [
                    'contents' => static::getPracticeGroupBadge( $practiceGroupNavItem[ 'practiceGroup' ] ) .
                        wfMessage( 'practicegroups-practicegrouptalk-action',
                            static::getMainArticleText( $practiceGroupNavItem[ 'title' ] ) )->text(),
                    'href' => $practiceGroupTalkTitle->getLinkURL()
                ];

                $navManager->addDropdownItem( $navId, $discussionNavItemAttribs );
            }
        }
    }

    public static function validateAffiliatedEmail( PracticeGroup $practiceGroup, string $affiliatedEmail ): Status {
        $result = Status::newGood();

        if( !$practiceGroup ) {
            $result->fatal( 'practicegroups-error-notdefined' );

            return $result;
        }

        $affiliatedEmail = filter_var( $affiliatedEmail, FILTER_VALIDATE_EMAIL );

        if( !$affiliatedEmail ) {
            $result->fatal( 'practicegroups-error-emailnotvalid' );

            return $result;
        }

        $affiliatedDomains = $practiceGroup->getAffiliatedDomains();

        if( !$affiliatedDomains ) {
            $result->fatal( 'practicegroups-error-practicegroup-noaffiliateddomains' );

            return $result;
        }

        preg_match( '/(.*)@(.*)$/', $affiliatedEmail, $affiliatedEmailParts );

        $affiliatedEmailDomain = $affiliatedEmailParts[ 2 ];

        $validated = false;

        foreach( $affiliatedDomains as $affiliatedDomain ) {
            # IMPORTANT: If the user has an email that is more specific than the affiliated domain, we want to match.
            # However, if the affiliated domain is more specific than the email domain, we don't want to match.
            if( stripos( $affiliatedEmailDomain, $affiliatedDomain ) !== false ) {
                $validated = true;
            }
        }

        if( !$validated ) {
            $result->fatal( 'practicegroups-error-emailnotaffiliated' );

            return $result;
        }

        return $result;
    }

    /**
     * This function returns whether a user should be able to read a practice group title.
     * If the title passed is not a practice group title, it will return false.
     * @param Title|string $title
     * @param User|null $user
     * @return bool
     */
    public static function userCanReadPracticeGroupTitle( $title, User $user = null ): bool {
        if( PracticeGroups::isUserPracticeGroupSysop( $user ) ) {
            return true;
        }

        if( !$title ) {
            return false;
        } elseif( !$title instanceof Title ) {
            $title = Title::newFromText( $title );
        }

        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        if( !$practiceGroup ) {
            return false;
        }

        return $practiceGroup->userCanReadPage( $title->getArticleID(), $user );
    }

    /**
     * Hides output until fully rendered by wrapping output in an invisible div which is removed when the document is ready
     * @param OutputPage $out
     */
    public static function wrapRenderShield( OutputPage &$out ) {
        $out->prependHTML(
            Html::openElement( 'div', [
                'class' => 'practicegroups-rendershield',
                'style' => 'visibility: hidden;'
            ] )
        );

        $out->addHTML(
            Html::closeElement( 'div' )
        );
    }
}