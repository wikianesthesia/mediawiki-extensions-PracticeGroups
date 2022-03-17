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

    protected static $membershipButtonCount = [];

    protected static $practiceGroupLinkedNamespaces = [];
    protected static $practiceGroupNamespaces = [];
    protected static $practiceGroupNotesNamespaces = [];
    protected static $practiceGroupTalkNamespaces = [];

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
        $allowedPracticeGroups = static::getAllPublicPracticeGroups();

        $user = RequestContext::getMain()->getUser();

        if( $user->isRegistered() ) {
            $practiceGroupsUsers = PracticeGroupsUser::getAllForUser( $user->getId() );

            foreach( $practiceGroupsUsers as $practiceGroupsUser ) {
                $practiceGroup = $practiceGroupsUser->getPracticeGroup();

                if( !array_key_exists( $practiceGroup->getId(), $allowedPracticeGroups ) &&
                    $practiceGroupsUser->isActive() ) {
                    $allowedPracticeGroups[ $practiceGroup->getId() ] = $practiceGroup;
                }
            }
        }

        return $allowedPracticeGroups;
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

    public static function getExtensionName(): string {
        return self::EXTENSION_NAME;
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

        return preg_replace( '/' . $practiceGroupTitle->getRootText() . '\/?/', '', $practiceGroupTitle->getText() );
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

    public static function getPracticeGroupFromTitle( $title ) {
        if( !$title ) {
            return false;
        }

        if( !$title instanceof Title ) {
            $title = Title::newFromText( $title );
        }

        if( $title->getNamespace() != NS_PRACTICEGROUP ) {
            return false;
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

    public static function isTitlePracticeGroupArticle( $title ) {
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

    public static function isUserPracticeGroupSysop( User $user ) {
        if( MediaWikiServices::getInstance()->getPermissionManager()->userHasRight(
                $user,
                'practicegroups-sysop'
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param string $search
     * @return false|Title[]
     */
    public static function searchPracticeGroupArticleTitles( string $search ) {
        if( strpos( $search, ':' ) !== false ) {
            return false;
        }

        $myUser = RequestContext::getMain()->getUser();

        if( !$myUser->isRegistered() ) {
            return false;
        }

        $myPracticeGroupsUsers = PracticeGroupsUser::getAllForUser( $myUser->getId() );

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

        $practiceGroupsUsers = PracticeGroupsUser::getAllForUser( $user->getId() );

        $practiceGroupNavItems = [];
        $discussionNavItems = [];

        foreach( $practiceGroupsUsers as $practiceGroupsUser ) {
            if( !$practiceGroupsUser->isActive() ) {
                continue;
            }

            $practiceGroup = $practiceGroupsUser->getPracticeGroup();

            $practiceGroupArticleTitle = Title::newFromText( 'PracticeGroup:' . $practiceGroup->getDBKey() . '/' . $mainArticleTitle->getText() );
            $practiceGroupArticleLinkMessage = $practiceGroupArticleTitle->exists() ? 'practicegroups-practicegrouparticle-action' : 'practicegroups-practicegrouparticle-actioncreate';
            $practiceGroupArticleLinkText = wfMessage( $practiceGroupArticleLinkMessage, $practiceGroup->getShortName() )->text();

            $practiceGroupNavItems[] = [
                'contents' => $practiceGroupArticleLinkText,
                'href' => $practiceGroupArticleTitle->getLocalURL()
            ];

            $practiceGroupArticleTalkTitle = $practiceGroupArticleTitle->getTalkPageIfDefined();
            $practiceGroupArticleTalkLinkText = wfMessage( 'practicegroups-practicegrouptalk-action', $practiceGroup->getShortName() )->text();

            $discussionNavItems[] = [
                'contents' => $practiceGroupArticleTalkLinkText,
                'href' => $practiceGroupArticleTalkTitle->getLocalURL()
            ];
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
            $navItemAttribs[ 'contents' ] = $tabIcon . Html::rawElement( 'span', [
                    'class' => 'nav-label'
                ], $practiceGroupNavItems[ 0 ][ 'contents' ] );

            $navItemAttribs[ 'href' ] = $practiceGroupNavItems[ 0 ][ 'href' ];

            $navManager->addNavItem( $navId, $navItemAttribs );
        } else {
            $navItemAttribs[ 'contents' ] = $tabIcon . Html::rawElement( 'span', [
                    'class' => 'nav-label'
                ], wfMessage( 'practicegroups-practicegrouparticles' )->text() );

            $navManager->addNavItem( $navId, $navItemAttribs );

            foreach( $practiceGroupNavItems as $practiceGroupNavItem ) {
                $navManager->addDropdownItem( $navId, $practiceGroupNavItem );
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

        if( !empty( $discussionNavItems ) ) {
            $navId = 'discussion';

            $navItem = $navManager->getNavItem( $navId );

            if( !isset( $navItem[ 'dropdownItems' ] ) ) {
                $navManager->addDropdownItem( $navId, [
                    'href' => $navItem[ 'href' ]
                ], wfMessage( 'practicegroups-maintalk', )->text() );
            }

            foreach( $discussionNavItems as $discussionNavItem ) {
                $navManager->addDropdownItem( $navId, $discussionNavItem );
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
     * @param Title $title
     * @return bool
     */
    public static function userCanReadPracticeGroupTitle( Title $title, $user = null ): bool {
        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        if( !$practiceGroup ) {
            return false;
        }

        return $practiceGroup->userCanReadPage( $title->getArticleID() );
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