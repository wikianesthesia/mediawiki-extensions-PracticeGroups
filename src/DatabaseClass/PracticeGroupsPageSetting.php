<?php

namespace PracticeGroups\DatabaseClass;

use DatabaseClasses\DatabaseClass;
use DatabaseClasses\DatabaseProperty;
use RequestContext;
use Status;
use Title;
use User;

class PracticeGroupsPageSetting extends DatabaseClass {
    protected static $properties = [ [
            'name' => 'practicegroups_page_setting_id',
            'autoincrement' => true,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ],[
            'name' => 'practicegroup_id',
            'required' => true, // Important, if true a deletion of the PracticeGroup will also delete this object
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ], [
            'name' => 'page_id',
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER,
            'validator' => self::class . '::validatePage'
        ], [
            'name' => 'timestamp',
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ], [
            'name' => 'user_id',
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER,
            'validator' => DatabaseProperty::class . '::validateUser'
        ], [
            'name' => 'privacy',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER,
            'validator' => self::class . '::validatePrivacy'
        ]
    ];

    protected static $schema = [
        'tableName' => 'practicegroups_page_settings',
        'orderBy' => 'practicegroup_id, page_id, timestamp DESC',
        'primaryKey' => 'practicegroups_page_setting_id',
        'relationships' => [ [
                'propertyName' => 'practicegroup_id',
                'relatedClassName' => PracticeGroup::class,
                'relatedPropertyName' => 'practiceGroupsPageSettingIds',
                'relationshipType' => DatabaseClass::RELATIONSHIP_ONE_TO_MANY
            ]
        ],
        'uniqueFields' => []
    ];

    public const PRIVACY_INHERIT = 0;
    public const PRIVACY_PRIVATE = 1;
    public const PRIVACY_PUBLIC = 2;

    public const VALID_PRIVACY = [
        self::PRIVACY_INHERIT,
        self::PRIVACY_PRIVATE,
        self::PRIVACY_PUBLIC
    ];


    /**
     * @return string
     */
    public function __toString(): string {
        $str = '';

        if( $this->getPageId() ) {
            $str .= $this->getTitle()->getText();
        }

        return $str;
    }

    /**
     * @param int $practiceGroupId
     * @return static[]|false
     */
    public static function getAllForPracticeGroup( int $practiceGroupId ) {
        return static::getAll( [ 'practicegroup_id' => $practiceGroupId ] );
    }

    /**
     * @param int $pageId
     * @return static[]|false
     */
    public static function getAllForPage( int $pageId ) {
        return static::getAll( [ 'page_id' => $pageId ] );
    }

    /**
     * @param int $pageId
     * @return static|false
     */
    public static function getCurrentForPage( int $pageId ) {
        $currentPracticeGroupPageSettingIds = static::getAll(
            [ 'page_id' => $pageId ],
            [ 'LIMIT' => 1 ]
        );

        return reset( $currentPracticeGroupPageSettingIds );
    }

    /**
     * @param int $pageId
     * @return Status
     */
    public static function validatePage( int $pageId ): Status {
        $result = Status::newGood();

        if( $pageId && !Title::newFromID( $pageId )->exists() ) {
            $result->fatal( 'practicegroups-error-invalid-pageid' );

            return $result;
        }

        return $result;
    }

    public static function validatePrivacy( int $privacy ): Status {
        $result = Status::newGood();

        if( !in_array( $privacy, self::VALID_PRIVACY ) ) {
            $result->fatal( 'practicegroups-error-invalid-privacy' );

            return $result;
        }

        return $result;
    }

    /**
     * @return int|false
     */
    public function getPageId() {
        return $this->getValue( 'page_id' );
    }

    /**
     * @return int|false
     */
    public function getPracticeGroupId() {
        return $this->getValue( 'practicegroup_id' );
    }

    /**
     * @return null|PracticeGroup
     */
    public function getPracticeGroup() {
        return PracticeGroup::getFromId( $this->getValue( 'practicegroup_id' ) );
    }

    /**
     * @return int|false
     */
    public function getPrivacy() {
        return $this->getValue( 'privacy' );
    }

    /**
     * @return User|false
     * @throws \MWException
     */
    public function getTimestamp() {
        return $this->getValue( 'timestamp' );
    }

    /**
     * @return Title|false
     */
    public function getTitle() {
        return Title::newFromId( $this->getValue( 'page_id' ) );
    }

    /**
     * @return int|false
     */
    public function getUserId() {
        return $this->getValue( 'user_id' );
    }

    /**
     * @return User|false
     * @throws \MWException
     */
    public function getUser() {
        $userId = $this->getValue( 'user_id' );

        if( $userId) {
            return User::newFromId( $userId );
        } else {
            return false;
        }
    }

    /**
     * @param string $action
     * @return Status
     */
    public function hasRight( string $action ): Status {
        $result = Status::newGood();

        $genericErrorMessage = 'practicegroups-error-permissiondenied';

        // Page settings can only be inserted
        if( $action === 'delete' || $action === 'edit' ) {
            $result->fatal( $genericErrorMessage );

            return $result;
        }

        $userId = RequestContext::getMain()->getUser()->getId();

        if( !$this->getPracticeGroup()->isUserAdmin( $userId ) ) {
            $result->fatal( $genericErrorMessage );

            return $result;
        }

        return $result;
    }
}