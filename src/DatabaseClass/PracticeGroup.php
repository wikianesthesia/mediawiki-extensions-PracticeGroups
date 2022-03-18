<?php

namespace PracticeGroups\DatabaseClass;

use DatabaseClasses\DatabaseClass;
use Hooks;
use PracticeGroups\PracticeGroups;
use RequestContext;
use Status;
use Title;
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

    protected static $myPracticeGroupsUsers = [];

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

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getActivePracticeGroupsUsers() {
        return static::sortPracticeGroupsUsers(
            PracticeGroupsUser::getAll( [
                'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
                'active_since > 0'
            ] ) );
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getAdminPracticeGroupsUsers() {
        return static::sortPracticeGroupsUsers(
            PracticeGroupsUser::getAll( [
                'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
                'active_since > 0',
                'admin > 0'
            ] ) );
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
        $allPracticeGroupsUsers = static::getObjectsForIds( PracticeGroupsUser::class, $this->getValue( 'practiceGroupsUserIds' ) );

        return static::sortPracticeGroupsUsers( $allPracticeGroupsUsers );
    }

    public function getArticles() {
        $dbKey = $this->getDBKey();

        if( !$dbKey ) {
            return false;
        }

        return $this->getDashboardTitle()->getSubpages();
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

    public function getFullURL(): string {
        $dashboardTitle = $this->getDashboardTitle();

        return $dashboardTitle ? $dashboardTitle->getFullURL() : '';
    }

    public function getLinkURL(): string {
        $dashboardTitle = $this->getDashboardTitle();

        return $dashboardTitle ? $dashboardTitle->getLinkURL() : '';
    }

    /**
     * @return Title|null
     */
    public function getDashboardTitle() {
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
        return static::sortPracticeGroupsUsers(
            PracticeGroupsUser::getAll( [
                'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
                'active_since' => 0,
                'invited_since > 0'
        ] ) );
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getPendingPracticeGroupsUsers() {
        return static::sortPracticeGroupsUsers(
            PracticeGroupsUser::getAll( [
                'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
                'active_since' => 0
        ] ) );
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
     * @param int $user_id
     * @return false|PracticeGroupsUser
     */
    public function getPracticeGroupsUserForUser( int $user_id ) {
        $isMyUser = RequestContext::getMain()->getUser()->isRegistered() && $user_id == RequestContext::getMain()->getUser()->getId();

        if( $isMyUser && isset( static::$myPracticeGroupsUsers[ $this->getId() ] ) ) {
            return static::$myPracticeGroupsUsers[ $this->getId() ];
        }

        $practiceGroupsUser = PracticeGroupsUser::getAll( [
            'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
            'user_id' => $user_id
        ] );

        if( $practiceGroupsUser ) {
            $practiceGroupsUser = array_shift( $practiceGroupsUser );
        }

        if( $isMyUser ) {
            static::$myPracticeGroupsUsers[ $this->getId() ] = $practiceGroupsUser;
        }

        return $practiceGroupsUser;
    }

    /**
     * @return PracticeGroupsUser[]|false
     */
    public function getRequestedPracticeGroupsUsers() {
        return static::sortPracticeGroupsUsers(
            PracticeGroupsUser::getAll( [
                'practicegroup_id' => $this->getValue( 'practicegroup_id' ),
                'active_since' => 0,
                'requested_since > 0'
        ] ) );
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
        $myPracticeGroupsUser = $this->getPracticeGroupsUserForUser( $myUser->getId() );

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
     * @param int $user_id
     * @return bool
     */
    public function isUserAdmin( int $user_id ): bool {
        $practiceGroupsUser = $this->getPracticeGroupsUserForUser( $user_id );

        if( $practiceGroupsUser && $practiceGroupsUser->isAdmin() ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param int $user_id
     * @return bool
     */
    public function isUserActiveMember( int $user_id ): bool {
        $practiceGroupsUser = $this->getPracticeGroupsUserForUser( $user_id );

        if( $practiceGroupsUser && $practiceGroupsUser->isActive() ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $user_id
     * @return bool
     */
    public function isUserPendingMember( int $user_id ): bool {
        $practiceGroupsUser = $this->getPracticeGroupsUserForUser( $user_id );

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

    public function save( bool $test = false ): Status {
        $result = parent::save( $test );
        $resultValue = $result->getValue();

        if( !$result->isOK() ) {
            return $result;
        }

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

            static::$myPracticeGroupsUsers[ $this->getId() ] = $practiceGroupsUser;
        }

        if( $resultValue[ 'action' ] === static::ACTION_CREATE ) {
            Hooks::run( 'PracticeGroupCreated', [ $this ] );
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

            if( $user->isRegistered() && $this->isUserActiveMember( $user->getId() ) ) {
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
            $this->isUserActiveMember( $user->getId() ) ||
            PracticeGroups::isUserPracticeGroupSysop( $user );
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