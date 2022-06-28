<?php

namespace PracticeGroups\DatabaseClass;

use DatabaseClasses\DatabaseClass;
use Hooks;
use MediaWiki\MediaWikiServices;
use PracticeGroups\PracticeGroups;
use RequestContext;
use Status;
use Title;
use TitleArray;
use User;

class PracticeGroup extends DatabaseClass {

    protected static $properties = [ [
            'name' => 'practicegroup_id',
            'autoincrement' => true,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ], [
            'name' => 'dbkey',
            'required' => true,
            'size' => 25,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_STRING,
            'validator' => self::class . '::isValidDBKey'
        ], [
            'name' => 'name_full',
            'required' => true,
            'size' => 100,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_STRING,
            'validator' => self::class . '::isValidNameFull'
        ], [
            'name' => 'name_short',
            'required' => true,
            'size' => 25,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_STRING,
            'validator' => self::class . '::isValidNameShort'
        ], [
            'name' => 'color_primary',
            'size' => 25,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_COLOR
        ], [
            'name' => 'color_secondary',
            'size' => 25,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_COLOR
        ], [
            'name' => 'preserve_main_title_links',
            'defaultValue' => 1,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_BOOLEAN
        ], [
            'name' => 'view_by_public',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_BOOLEAN
        ], [
            'name' => 'join_by_public',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_BOOLEAN
        ], [
            'name' => 'any_member_add_user',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_BOOLEAN
        ], [
            'name' => 'join_by_request',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_BOOLEAN
        ], [
            'name' => 'join_by_affiliated_email',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_BOOLEAN
        ], [
            'name' => 'affiliated_domains',
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_STRING,
            'validator' => self::class . '::isValidAffiliatedDomains'
        ], [
            'name' => 'practiceGroupsPageSettingIds',
            'source' => DatabaseClass::SOURCE_RELATIONSHIP,
            'type' => DatabaseClass::TYPE_ARRAY
        ], [
            'name' => 'practiceGroupsUserIds',
            'source' => DatabaseClass::SOURCE_RELATIONSHIP,
            'type' => DatabaseClass::TYPE_ARRAY
        ]
    ];

    protected static $schema = [
        'tableName' => 'practicegroups',
        'orderBy' => 'name_full',
        'primaryKey' => 'practicegroup_id',
        'relationships' => [ [
                'propertyName' => 'practiceGroupsUserIds',
                'autoload' => false,
                'relatedClassName' => PracticeGroupsUser::class,
                'relatedPropertyName' => 'practicegroup_id',
                'relationshipType' => DatabaseClass::RELATIONSHIP_MANY_TO_ONE
            ], [
                'propertyName' => 'practiceGroupsPageSettingIds',
                'autoload' => false,
                'relatedClassName' => PracticeGroupsPageSetting::class,
                'relatedPropertyName' => 'practicegroup_id',
                'relationshipType' => DatabaseClass::RELATIONSHIP_MANY_TO_ONE
            ]
        ],
        'uniqueFields' => [
            [ 'dbkey' ],
            [ 'name_full' ],
            [ 'name_short' ]
        ]
    ];

    protected const CACHE_KEY_ARTICLES = 'PracticeGroups\PracticeGroupArticles';

    protected const CACHE_KEY_USERS_ALL = 'PracticeGroups\PracticeGroupsUsersAll';
    protected const CACHE_KEY_USERS_ACTIVE = 'PracticeGroups\PracticeGroupsUsersActive';
    protected const CACHE_KEY_USERS_ADMIN = 'PracticeGroups\PracticeGroupsUsersAdmin';
    protected const CACHE_KEY_USERS_INVITED = 'PracticeGroups\PracticeGroupsUsersInvited';
    protected const CACHE_KEY_USERS_PENDING = 'PracticeGroups\PracticeGroupsUsersPending';
    protected const CACHE_KEY_USERS_REQUESTED = 'PracticeGroups\PracticeGroupsUsersRequested';

    protected const CACHE_KEYS_USERS = [
        self::CACHE_KEY_USERS_ACTIVE,
        self::CACHE_KEY_USERS_ADMIN,
        self::CACHE_KEY_USERS_INVITED,
        self::CACHE_KEY_USERS_PENDING,
        self::CACHE_KEY_USERS_REQUESTED
    ];

    /**
     * @return string
     */
    public function __toString() {
        $str = '';

        if( $this->getValue( 'name_full' ) ) {
            $str = $this->getValue( 'name_full' );
        } elseif( $this->getValue( 'dbkey' ) ) {
            $str = $this->getValue( 'dbkey' );
        }

        return $str;
    }

    /**
     * @param string $dbKey
     * @return PracticeGroup|false
     */
    public static function getFromDBKey( string $dbKey ) {
        return static::getFromUniqueFieldGroupValues( [ 'dbkey' => $dbKey ] );
    }

    public static function isValidAffiliatedDomains( string $affiliatedDomains ): Status {
        $result = Status::newGood();

        $domains = explode(',', $affiliatedDomains );
        $invalidDomains = [];

        foreach( $domains as $domain ) {
            if( !checkdnsrr( idn_to_ascii( $domain ), 'MX') ) {
                $invalidDomains[] = $domain;
            }
        }

        if( !empty( $invalidDomains ) ) {
            $result->fatal( wfMessage(
                'practicegroups-error-affiliateddomainsnotvalid',
                implode(', ', $invalidDomains)
            ) );
        }

        return $result;
    }

    public static function isValidDBKey( string $dbKey ) {
        $status = Status::newGood();

        $minLength = 2;
        $maxLength = static::getMaxLength( 'dbkey' );

        $length = strlen( $dbKey );

        if( $length < $minLength || $length > $maxLength ) {
            $status->fatal( wfMessage(
                'practicegroups-error-invalidlength',
                $minLength,
                $maxLength
            ) );

            return $status;
        }

        return $dbKey == PracticeGroup::makeValidDBKey( $dbKey );
    }

    public static function isValidNameFull( string $nameFull ): Status {
        $status = Status::newGood();

        $minLength = 2;
        $maxLength = static::getMaxLength( 'name_full' );

        $length = strlen( $nameFull );

        if( $length < $minLength || $length > $maxLength ) {
            $status->fatal( wfMessage(
                'practicegroups-error-invalidlength',
                $minLength,
                $maxLength
            ) );
        }

        return $status;
    }

    public static function isValidNameShort( string $nameShort ): Status {
        $status = Status::newGood();

        $minLength = 2;
        $maxLength = static::getMaxLength( 'name_short' );

        $length = strlen( $nameShort );

        if( $length < $minLength || $length > $maxLength ) {
            $status->fatal( wfMessage(
                'practicegroups-error-invalidlength',
                $minLength,
                $maxLength
            ) );
        }

        return $status;
    }

    /**
     * @param string $dbKey
     * @return false|string
     */
    public static function makeValidDBKey( string $dbKey ) {
        if( !$dbKey ) {
            return false;
        }

        # Remove all characters before the first capital letter
        $dbKey = preg_replace( '/^[^A-Z]+(.*)/', '\1', $dbKey );

        # Only allow special characters from a limited set
        $allowedExtraCharacters = '_-';

        # Remove any other characters
        $dbKey = preg_replace( '/[^' . preg_quote( $allowedExtraCharacters ) . 'A-Za-z0-9]+/', '', $dbKey );

        # Make sure allowed special characters only occur once in a row
        foreach( str_split( $allowedExtraCharacters ) as $extraCharacter ) {
            $dbKey = preg_replace( '/[' . preg_quote( $extraCharacter ) . ']+/', $extraCharacter, $dbKey );
        }

        # Make length of string valid
        $dbKey = substr( $dbKey, 0, static::getMaxLength( 'dbkey' ) );

        return $dbKey;
    }

    /**
     * @return bool
     */
    public function canJoinByAffiliatedEmail(): bool {
        return (bool) $this->getValue( 'join_by_affiliated_email' ) && $this->getAffiliatedDomains();
    }

    /**
     * @return bool
     */
    public function canJoinByPublic(): bool {
        return (bool) $this->getValue( 'join_by_public' );
    }

    /**
     * @return bool
     */
    public function canJoinByRequest(): bool {
        return (bool) $this->getValue( 'join_by_request' );
    }

    /**
     * @return bool
     */
    public function canAnyMemberAddUser(): bool {
        return (bool) $this->getValue( 'any_member_add_user' );
    }

    /**
     * @return bool
     */
    public function canViewByPublic(): bool {
        return (bool) $this->getValue( 'view_by_public' );
    }

    public function delete( bool $test = false ): Status {
        $result = parent::delete( $test );

        if( !$test ) {
            $this->purgePracticeGroupsUsers();

            // Purge regardless of whether the user is a member
            PracticeGroups::purgeMyPracticeGroupsUsers();
        }

        return $result;
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getActivePracticeGroupsUsers() {
        return $this->getPracticeGroupsUsers( self::CACHE_KEY_USERS_ACTIVE, function( PracticeGroup $practiceGroup ) {
            return static::sortPracticeGroupsUsers(
                PracticeGroupsUser::getAll( [
                    'practicegroup_id' => $practiceGroup->getValue( 'practicegroup_id' ),
                    'active_since > 0'
                ] ) );
        } );
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getAdminPracticeGroupsUsers() {
        return $this->getPracticeGroupsUsers( self::CACHE_KEY_USERS_ADMIN, function( PracticeGroup $practiceGroup ) {
            return static::sortPracticeGroupsUsers(
                PracticeGroupsUser::getAll( [
                    'practicegroup_id' => $practiceGroup->getValue( 'practicegroup_id' ),
                    'active_since > 0',
                    'admin > 0'
                ] ) );
        } );
    }

    /**
     * @return string[]
     */
    public function getAffiliatedDomains(): array {
        return explode( ',', $this->getValue( 'affiliated_domains' ) );
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getAllPracticeGroupsUsers() {
        return $this->getPracticeGroupsUsers( self::CACHE_KEY_USERS_ALL, function( PracticeGroup $practiceGroup ) {
            return static::sortPracticeGroupsUsers(
                static::getObjectsForIds(
                    PracticeGroupsUser::class,
                    $practiceGroup->getValue( 'practiceGroupsUserIds' )
                ) );
        } );
    }

    /**
     * @return Title[]
     */
    public function getArticles(): array {
        global $wgDatabaseClassesCacheTTL;

        $cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

        $practiceGroup = $this;

        $callback = function( $oldValue, &$ttl, array &$setOpts ) use ( $practiceGroup ) {
            $titles = [];

            $dashboardTitle = $practiceGroup->getDashboardTitle();
            if( $dashboardTitle ) {
                foreach( $dashboardTitle->getSubpages() as $title ) {
                    $titles[] = $title;
                }
            }

            return $titles;
        };

        return $cache->getWithSetCallback(
            $cache->makeKey( self::CACHE_KEY_ARTICLES, $this->getValue( 'practicegroup_id' ) ),
            $wgDatabaseClassesCacheTTL,
            $callback
        );
    }

    /**
     * @return string
     */
    public function getDBKey(): string {
        return (String) $this->getValue( 'dbkey' );
    }

    /**
     * @return string
     */
    public function getFullName(): string {
        return (String) $this->getValue( 'name_full' );
    }

    public function getFullURL( string $fragment = '', string $query = '' ): string {
        $dashboardTitle = $this->getDashboardTitle();

        if( $fragment ) {
            $dashboardTitle->setFragment( $fragment );
        }

        return $dashboardTitle ? $dashboardTitle->getFullURL( $query ) : '';
    }

    public function getLinkURL( string $fragment = '', string $query = '' ): string {
        $dashboardTitle = $this->getDashboardTitle();

        if( $fragment ) {
            $dashboardTitle->setFragment( $fragment );
        }

        return $dashboardTitle ? $dashboardTitle->getLinkURL( $query ) : '';
    }

    /**
     * @return Title|null
     */
    public function getDashboardTitle(): ?Title {
        if( !$this->getDBKey() ) {
            return null;
        }

        return Title::newFromText( $this->getDBKey(), NS_PRACTICEGROUP );
    }

    /**
     * @return string
     */
    public function getPrefixedDBKey(): string {
        $prefixedDBKey = '';

        $dashboardTitle = $this->getDashboardTitle();

        if( !$dashboardTitle ) {
            return $prefixedDBKey;
        }

        return $dashboardTitle->getPrefixedDBkey();
    }

    /**
     * @return string
     */
    public function getPrimaryColor(): string {
        return (String) $this->getValue( 'color_primary' );
    }

    /**
     * @return string
     */
    public function getSecondaryColor(): string {
        return (String) $this->getValue( 'color_secondary' );
    }

    /**
     * @return string
     */
    public function getShortName(): string {
        return (String) $this->getValue( 'name_short' );
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getInvitedPracticeGroupsUsers() {
        return $this->getPracticeGroupsUsers( self::CACHE_KEY_USERS_INVITED, function( PracticeGroup $practiceGroup ) {
            return static::sortPracticeGroupsUsers(
                PracticeGroupsUser::getAll( [
                    'practicegroup_id' => $practiceGroup->getValue( 'practicegroup_id' ),
                    'active_since' => 0,
                    'invited_since > 0'
                ] ) );
        } );
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getPendingPracticeGroupsUsers() {
        return $this->getPracticeGroupsUsers( self::CACHE_KEY_USERS_PENDING, function( PracticeGroup $practiceGroup ) {
            return static::sortPracticeGroupsUsers(
                PracticeGroupsUser::getAll( [
                    'practicegroup_id' => $practiceGroup->getValue( 'practicegroup_id' ),
                    'active_since' => 0
                ] ) );
        } );
    }

    /**
     * @param string $email
     * @return false|PracticeGroupsUser
     */
    public function getPracticeGroupsUserForEmail( string $email ) {
        $practiceGroupsUser = PracticeGroupsUser::getAll( [
            'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
            'affiliated_email' => $email
        ] );

        if( $practiceGroupsUser ) {
            $practiceGroupsUser = array_shift( $practiceGroupsUser );
        }

        return $practiceGroupsUser;
    }

    /**
     * @param User $user
     * @return false|PracticeGroupsUser
     */
    public function getPracticeGroupsUserForUser( User $user ) {
        return PracticeGroups::getPracticeGroupsUserForUser( $this, $user );
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getRequestedPracticeGroupsUsers() {
        return $this->getPracticeGroupsUsers( self::CACHE_KEY_USERS_REQUESTED, function( PracticeGroup $practiceGroup ) {
            return static::sortPracticeGroupsUsers(
                PracticeGroupsUser::getAll( [
                    'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
                    'active_since' => 0,
                    'requested_since > 0'
                ] ) );
        } );
    }

    public function hasRight( string $action ): Status {
        $result = Status::newGood();

        $genericErrorMessage = 'practicegroups-error-permissiondenied';

        $myUser = RequestContext::getMain()->getUser();

        if( !$myUser->isRegistered() ) {
            # All actions require a user to be logged in
            $result->fatal( $genericErrorMessage );

            return $result;
        }

        # Allow all actions for PracticeGroups sysops
        if( PracticeGroups::isUserPracticeGroupSysop( $myUser ) ) {
            return $result;
        }

        if( $action === static::ACTION_CREATE ) {
            # Creation permissions just depend on the mediawiki user right which the parent will check
            return parent::hasRight( $action );
        }

        # All other actions require the requesting user to be an administrator of the practice group
        $myPracticeGroupsUser = $this->getPracticeGroupsUserForUser( $myUser );

        if( !$myPracticeGroupsUser ) {
            # Requesting user is not in the practice group
            $result->fatal( $genericErrorMessage );

            return $result;
        } elseif( !$myPracticeGroupsUser->isAdmin() ) {
            # Requesting user is not an administrator of the practice group
            $result->fatal( $genericErrorMessage );

            return $result;
        }

        if( $action === static::ACTION_EDIT ) {
            $dbPracticeGroup = PracticeGroup::getFromId( $this->getId() );

            if( $this->getDBKey() != $dbPracticeGroup->getDBKey() ) {
                # A practice group's dbkey cannot be changed.
                $result->fatal( $genericErrorMessage );

                return $result;
            }

        } elseif( $action === static::ACTION_DELETE ) {
            # Deleting a practice group could be a huge deal, so for now, we're going to just forbid it altogether.
            $result->fatal( $genericErrorMessage );

            return $result;
        }

        return $result;
    }


    /**
     * @param User $user
     * @return bool
     */
    public function isUserAdmin( User $user ): bool {
        $practiceGroupsUser = $this->getPracticeGroupsUserForUser( $user );

        if( $practiceGroupsUser && $practiceGroupsUser->isAdmin() ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param User $user
     * @return bool
     */
    public function isUserActiveMember( User $user ): bool {
        $practiceGroupsUser = $this->getPracticeGroupsUserForUser( $user );

        if( $practiceGroupsUser && $practiceGroupsUser->isActive() ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isUserPendingMember( User $user ): bool {
        $practiceGroupsUser = $this->getPracticeGroupsUserForUser( $user );

        if( $practiceGroupsUser && $practiceGroupsUser->isPending() ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function preserveMainTitleLinks(): bool {
        return (bool) $this->getValue( 'preserve_main_title_links' );
    }

    public function purgeArticles(): void {
        $cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

        $cache->delete( $cache->makeKey( self::CACHE_KEY_ARTICLES, $this->getValue( 'practicegroup_id' ) ) );
    }

    public function purgePracticeGroupsUsers(): void {
        $cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

        foreach( self::CACHE_KEYS_USERS as $cacheKey ) {
            $cache->delete( $cache->makeKey( $cacheKey, $this->getValue( 'practicegroup_id' ) ) );
        }
    }

    public function save( bool $test = false ): Status {
        if( !$this->getValue( 'join_by_affiliated_email' ) ) {
            $this->setValue( 'affiliated_domains', '' );
        }

        $result = parent::save( $test );
        $resultValue = $result->getValue();

        if( !$result->isOK() ) {
            return $result;
        }

        if( !$test ) {
            if( !count( $this->getActivePracticeGroupsUsers() ) ) {
                # This should only happen when a practice group was just created.
                # In this case, we should create a new administrator practice group user for the requesting user.
                # hasRight() asserts that the user exists and is logged in, so we can skip that check here.
                $myUser = RequestContext::getMain()->getUser();

                $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
                    'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
                    'user_id' => $myUser->getId(),
                    'admin' => 1,
                    'active_since' => time(),
                    'approved_by_user_id' => $myUser->getId()
                ] );

                $result = $practiceGroupsUser->save();

                if( !$result->isOK() ) {
                    return $result;
                }

                PracticeGroups::purgeMyPracticeGroupsUsers();
            }

            $this->purgePracticeGroupsUsers();

            if( $resultValue[ 'action' ] === static::ACTION_CREATE ) {
                Hooks::run( 'PracticeGroupCreated', [ $this ] );
            }
        }

        return $result;
    }

    /**
     * @param int $pageId
     * @param User|null $user
     * @return bool
     */
    public function userCanReadPage( int $pageId, $user = null ): bool {
        $privacy = PracticeGroups::getEffectivePrivacyForPage( $pageId );

        if( $privacy === false ) {
            return false;
        }

        if( $privacy == PracticeGroupsPageSetting::PRIVACY_PUBLIC ) {
            return true;
        } else {
            $user = $user ?? RequestContext::getMain()->getUser();

            if( $user->isRegistered() && $this->isUserActiveMember( $user ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param null $user
     * @return bool
     */
    public function userCanView( $user = null ): bool {
        $user = $user ?? RequestContext::getMain()->getUser();

        return $this->canViewByPublic() ||
            $this->isUserActiveMember( $user ) ||
            PracticeGroups::isUserPracticeGroupSysop( $user );
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    protected function getPracticeGroupsUsers( string $cacheKey, callable $callback ) {
        global $wgDatabaseClassesCacheTTL;

        $cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

        $cacheCallback = function( $oldValue, &$ttl, array &$setOpts ) use ( $callback ) {
            return $callback( $this );
        };

        return $cache->getWithSetCallback(
            $cache->makeKey( $cacheKey, $this->getValue( 'practicegroup_id' ) ),
            $wgDatabaseClassesCacheTTL,
            $cacheCallback
        );
    }

    protected static function sortPracticeGroupsUsers( array $practiceGroupsUsers = [] ) {
        if( !is_array( $practiceGroupsUsers ) ) {
            return false;
        }

        uasort( $practiceGroupsUsers, function ( PracticeGroupsUser $a, PracticeGroupsUser $b ) {
            if( $a->isAdmin() && !$b->isAdmin() ) {
                return -1;
            } elseif( !$a->isAdmin() && $b->isAdmin() ) {
                return 1;
            } else {
                return strnatcasecmp( $a->getUser()->getRealName(), $b->getUser()->getRealName() );
            }
        } );

        return $practiceGroupsUsers;
    }
}