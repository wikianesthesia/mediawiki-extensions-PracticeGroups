<?php

namespace PracticeGroups\DatabaseClass;

use DatabaseClasses\DatabaseClass;
use DatabaseClasses\DatabaseProperty;
use Hooks;
use MailAddress;
use PracticeGroups\PracticeGroups;
use RequestContext;
use Status;
use Title;
use User;
use UserMailer;

class PracticeGroupsUser extends DatabaseClass {
    protected static $properties = [ [
            'name' => 'practicegroupsuser_id',
            'autoincrement' => true,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ],[
            'name' => 'practicegroup_id',
            'required' => true, // Important, if true a deletion of the PracticeGroup will also delete this object
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ], [
            'name' => 'user_id',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER,
            'validator' => self::class . '::validateUser' // Need to overload to allow 0
        ], [
            'name' => 'admin',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_BOOLEAN
        ], [
            'name' => 'active_since',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ], [
            'name' => 'invited_since',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ], [
            'name' => 'requested_since',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ], [
            'name' => 'request_reason',
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_STRING
        ], [
            'name' => 'awaiting_email_verification_since',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER
        ], [
            'name' => 'affiliated_email',
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_STRING
        ], [
            'name' => 'email_verification_code',
            'size' => 16,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_STRING
        ], [
            'name' => 'approved_by_user_id',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_UNSIGNED_INTEGER,
            'validator' => DatabaseProperty::class . '::validateUser'
        ], [
            'name' => 'display_order',
            'defaultValue' => 0,
            'source' => DatabaseClass::SOURCE_FIELD,
            'type' => DatabaseClass::TYPE_INTEGER
        ]
    ];

    protected static $schema = [
        'tableName' => 'practicegroups_users',
        'orderBy' => 'display_order, practicegroup_id',
        'primaryKey' => 'practicegroupsuser_id',
        'relationships' => [ [
                'propertyName' => 'practicegroup_id',
                'relatedClassName' => PracticeGroup::class,
                'relatedPropertyName' => 'practiceGroupsUserIds',
                'relationshipType' => DatabaseClass::RELATIONSHIP_ONE_TO_MANY
            ]
        ],
        'selectParams' => [
            'table' => array(
                't1' => 'practicegroups_users',
                't2' => 'practicegroups'
            ),
            'options' => array( 'ORDER BY' => 't1.display_order, t2.name_full' ),
            'joinConds' => array(
                't2' => array( 'INNER JOIN', array( 't1.practicegroup_id = t2.practicegroup_id' ) )
            )
        ],
        'uniqueFields' => []
    ];

    private $affiliatedEmailVerified = false;


    /**
     * @return string
     */
    public function __toString(): string {
        $str = '';

        if( $this->getUserId() ) {
            $str .= $this->getUser()->getRealName();
        } elseif( $this->getAffiliatedEmail() ) {
            $str .= $this->getAffiliatedEmail();
        }

        return $str;
    }

    /**
     * @param int $userId
     * @return static[]|false
     * @throws \MWException
     */
    public static function getAllForUser( int $userId ) {
        return static::getAll( [ 'user_id' => $userId ] );
    }

    /**
     * @param int $userId
     * @return static[]|false
     * @throws \MWException
     */
    public static function getAllInvitedByUser( int $userId ) {
        #TODO
        return [];
        # return static::getAll( [ 'user_id' => $userId ] );
    }

    public static function validateUser( int $userId ): Status {
        $result = Status::newGood();

        if( $userId != 0 && !User::newFromId( $userId )->loadFromDatabase() ) {
            $result->fatal( 'practicegroups-error-invalid-userid' );

            return $result;
        }

        return $result;
    }

    /**
     * @return User|false
     * @throws \MWException
     */
    public function getApprovedByUser() {
        if( !$this->isPrimaryKeySet() ) {
            return false;
        }

        $approvedByUserId = $this->getValue( 'approved_by_user_id' );

        if( $approvedByUserId) {
            return User::newFromId( $approvedByUserId );
        } else {
            return false;
        }
    }

    public function canAddUser(): bool {
        if( $this->exists() && $this->isActive() && ( $this->isAdmin() || $this->getPracticeGroup()->canAnyMemberAddUser() ) ) {
            return true;
        } else {
            return false;
        }
    }

    public function delete( bool $test = false ): Status {
        $result = Status::newGood();

        $practiceGroup = $this->getPracticeGroup();

        if( $this->isAdmin() && $practiceGroup && count( $practiceGroup->getAdminPracticeGroupsUsers() ) <= 1 ) {
            # The last admin cannot delete themself.
            $result->fatal( 'practicegroups-error-cantremovelastadmin' );

            return $result;
        }

        return parent::delete( $test );
    }

    /**
     * @return null|PracticeGroup
     */
    public function getPracticeGroup() {
        return PracticeGroup::getFromId( $this->getValue( 'practicegroup_id' ) );
    }

    /**
     * @return string|false
     */
    public function getAffiliatedEmail() {
        return $this->getValue( 'affiliated_email' );
    }

    /**
     * @return string|false
     */
    public function getRequestReason() {
        return $this->getValue( 'request_reason' );
    }

    /**
     * @return User|false
     */
    public function getUser() {
        return User::newFromId( $this->getValue( 'user_id' ) );
    }

    /**
     * @return int|false
     */
    public function getUserId() {
        return $this->getValue( 'user_id' );
    }

    /**
     * @param string $action
     * @return Status
     */
    public function hasRight( string $action ): Status {
        $result = Status::newGood();

        $genericErrorMessage = 'practicegroups-error-permissiondenied';

        # Conditionals in this function have been written less concisely in case hasRight() is modified to
        # return a Status and provide a detailed error message.

        # This completely replaces the default rights management
        # $result = parent::hasRight( $action );

        if( $this->affiliatedEmailVerified ) {
            # This private flag can only be set by verifyAffiliatedEmail(), which is diligent about making sure
            # that nothing inappropriate changes during an email verification event. Thus, it may proceed directly.

            return $result;
        }

        $myUser = RequestContext::getMain()->getUser();

        if( !$myUser->isRegistered() ) {
            $result->fatal( $genericErrorMessage );

            return $result;
        }

        $practiceGroup = $this->getPracticeGroup();
        $user = $this->getUser();

        # Get the PracticeGroupsUser for the requesting user. This may or may not exist.
        $myPracticeGroupsUser = $practiceGroup->getPracticeGroupsUserForUser( $myUser->getId() );

        ##
        # Some initial sanity checks which are relevant for any user not being deleted from the database
        ##

        # The PracticeGroup and User being acted upon must exist if an id is set.
        if( !$practiceGroup || ( $user->getId() > 0 && !$user->loadFromDatabase() ) ) {
            if( $action === static::ACTION_DELETE ) {
                # The one exception is if the PracticeGroupsUser becomes orphaned because
                # either the PracticeGroup or the User was deleted and this wasn't cleaned up properly.
                # In this case, allow deletion no matter other circumstances of the request.
                return $result;
            }

            $result->fatal( $genericErrorMessage );

            return $result;
        }

        if( $action !== static::ACTION_DELETE ) {
            # The practice group user must have at least one "since" field set to indicate a valid membership status
            if( !$this->getValue( 'active_since' )
                && !$this->getValue( 'invited_since' )
                && !$this->getValue( 'requested_since' )
                && !$this->getValue( 'awaiting_email_verification_since' )
            ) {
                $result->fatal( $genericErrorMessage );

                return $result;
            }
        }

        ##
        # Run specific checks based upon the requested action
        ##
        if( $action === static::ACTION_CREATE ) {
            # IMPORTANT: can't use $this->can/isX() helper functions since those check
            # $this->exist() and this user doesn't exist yet.

            if( count( $practiceGroup->getAllPracticeGroupsUsers() ) == 0 ) {
                # Special case
                # If the practice group has no users (i.e. was just created), allow creation
                # and require that the user is set to admin.

                if( !$this->getValue( 'admin' ) ) {
                    # The user being created must be set to admin
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }

                # No further checks, return ok
                return $result;
            }

            # Allow multiple instances with user_id = 0
            if( $this->getValue( 'user_id' ) && $practiceGroup->getPracticeGroupsUserForUser( $this->getValue( 'user_id' ) ) ) {
                $result->fatal( 'practicegroups-error-practicegroupsuser-alreadyexists' );

                return $result;
            }

            # Although probably not the most efficient structuring of these permission checks,
            # each field is evaluated independently for clarity.

            if( $this->getValue( 'admin' ) ) {
                # The user being created is set to admin

                if( !$myPracticeGroupsUser || !$myPracticeGroupsUser->isAdmin() ) {
                    # The creating user is not an admin of the practice group
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }

            if( $this->getValue( 'active_since' ) ) {
                # The user being created is set to active

                if( $user->getId() == 0 ) {
                    # Only valid wiki users can be active members
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $user->getId() != $myUser->getId() ) {
                    # The user is not creating themself
                    # Practice group users can only be created as active if they are self-created.
                    #
                    # i.e. Users invited to a practice group by another practice group user cannot be made
                    # active without that user's approval.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$practiceGroup->canJoinByPublic() ) {
                    # The practice group cannot be joined by the public
                    # This is the only scenario in which a practice group user can self-create as active
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$this->getValue( 'approved_by_user_id' ) ) {
                    # The approved by user id is not set
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !User::newFromId( $this->getValue( 'approved_by_user_id' ) )->loadFromDatabase() ) {
                    # The approved by user is not a valid user
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $this->getValue( 'approved_by_user_id' ) != $user->getId() ) {
                    # The approved by user id is not set to the self-creating user (for public groups)
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }

            if( $this->getValue( 'invited_since' ) ) {
                # The user being created is set to invited. Notably a user_id of 0 is allowed in case a user is being
                # invited that hasn't yet registered a wiki account.

                if( $user->getId() == 0 && !$this->getValue( 'affiliated_email' ) ) {
                    # Affiliated email must be set for non-wiki users that are invited
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$myPracticeGroupsUser ) {
                    # The inviting user is not a practice group user
                    # This will also reject if a user tries to invite themself.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$myPracticeGroupsUser->canAddUser() ) {
                    # The adding user cannot invite users to this practice group
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$this->getValue( 'approved_by_user_id' ) ) {
                    # The approved by user id is not set
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $this->getValue( 'approved_by_user_id' ) != $myUser->getId() ) {
                    # The approved by user id is not set to the inviting user
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $this->getValue( 'active_since' ) > 0 ) {
                    # Nonsensical case
                    # A user being created as invited should not also be active.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }

            if( $this->getValue( 'requested_since' ) ) {
                # The user being created is being set to requested

                if( $user->getId() == 0 ) {
                    # Only valid wiki users can be request to be members
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $user->getId() != $myUser->getId() ) {
                    # The user is not creating themself
                    # Practice group users can only be created as requested if they are self-created.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $practiceGroup->canJoinByPublic() ) {
                    # Nonsensical case
                    # A user should never be created as requested if the group can be joined by the public.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$practiceGroup->canJoinByRequest() ) {
                    # The practice group does not allow requests to join.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $this->getValue( 'active_since' ) > 0 ) {
                    # Nonsensical case
                    # A user being created as requested should not also be active.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $this->getValue( 'invited_since' ) > 0 ) {
                    # Nonsensical case
                    # A user being created as requested should not also be invited.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }

            if( $this->getValue( 'request_reason' ) ) {
                # The user being created has a request reason set

                if( $this->getValue( 'requested_since' ) == 0 ) {
                    # Nonsensical case
                    # A user being created should only have a request reason if they are set to requested.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }

            if( $this->getValue( 'awaiting_email_verification_since' ) ) {
                # The user being created is being set to awaiting email verification

                if( $user->getId() == 0 ) {
                    # Only valid wiki users can be await email verification
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $user->getId() != $myUser->getId() ) {
                    # The user is not creating themself
                    # Practice group users can only be created as awaiting email verification if they are self-created.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $practiceGroup->canJoinByPublic() ) {
                    # Nonsensical case
                    # A user should never be created as awaiting email verification if the group can be joined by the public.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$this->getValue( 'affiliated_email' ) ) {
                    # The user being created does not have an affiliated email address set.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$this->getValue( 'email_verification_code' ) ) {
                    # The user being created does not have an email verification code set.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $this->getValue( 'active_since' ) > 0 ) {
                    # Nonsensical case
                    # A user being created as awaiting email verification should not also be active.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $this->getValue( 'invited_since' ) > 0 ) {
                    # Nonsensical case
                    # A user being created as awaiting email verification should not also be invited.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }

            if( $this->getValue( 'affiliated_email' ) ) {
                # The user being created has an affiliated email set
                if( $practiceGroup->getPracticeGroupsUserForEmail( $this->getValue( 'affiliated_email' ) ) ) {
                    # Email is not unique in the practice group
                    $result->fatal( 'practicegroups-error-practicegroupsuser-emailalreadyexists' );

                    return $result;
                }

                if( $this->getValue( 'awaiting_email_verification_since' ) == 0 ) {
                    # User isn't awaiting email verification.
                    if( $this->getValue( 'invited_since' ) == 0 || $user->getId() > 0 ) {
                        # The only other valid case for affiliated_email to be set is if they are a non-wiki user
                        # being invited to the practice group.
                        $result->fatal( $genericErrorMessage );

                        return $result;
                    }
                }
            }

            if( $this->getValue( 'email_verification_code' ) ) {
                # The user being created has an email verification code set

                if( $this->getValue( 'awaiting_email_verification_since' ) == 0 ) {
                    if( $this->getValue( 'invited_since' ) == 0 || $user->getId() > 0 ) {
                        # The only other valid case for email_verification_code to be set is if they are a non-wiki user
                        # being invited to the practice group.
                        $result->fatal( $genericErrorMessage );

                        return $result;
                    }
                }
            }

            if( $this->getValue( 'approved_by_user_id' ) ) {
                # The user being created has a request_reason set

                if( !User::newFromId( $this->getValue( 'approved_by_user_id' ) )->loadFromDatabase() ) {
                    # The approved by user must be a valid wiki user
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
                elseif( $this->getValue( 'requested_since' ) > 0 ) {
                    # Nonsensical case
                    # A user being created should not have an approved by user id set
                    # if they are set to requested.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $this->getValue( 'awaiting_email_verification_since' ) > 0 ) {
                    # Nonsensical case
                    # A user being created should not have an approved by user id set
                    # if they are set to awaiting email confirmation.
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }
        } elseif( $action === static::ACTION_EDIT ) {
            if( $user->getId() == 0 ) {
                # There is no other valid scenario for a non-registered practicegroupsuser (i.e. invited user) to be edited
                # They are only created when invited, and deleted if rejected or canceled.
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            # Get the existing version of the practice group user being edited from the database
            $existingPracticeGroupsUser = static::getFromId( $this->getId() );

            if( !$myPracticeGroupsUser ) {
                # The editing user does not have a practice group user
                $result->fatal( $genericErrorMessage );

                return $result;
            } elseif( !$existingPracticeGroupsUser ) {
                # The practice group user being edited does not exist
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( $user->getId() != $myUser->getId() ) {
                # The user is not editing themselves. Make sure fields which can only be self-edited didn't change.

                if( $existingPracticeGroupsUser->getValue( 'display_order' ) != $this->getValue( 'display_order' )
                    || $existingPracticeGroupsUser->getValue( 'request_reason' ) != $this->getValue( 'request_reason' )
                    || $existingPracticeGroupsUser->getValue( 'affiliated_email' ) != $this->getValue( 'affiliated_email' )
                ) {
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }

            if( $existingPracticeGroupsUser->getValue( 'practicegroup_id' ) != $this->getValue('practicegroup_id' ) ) {
                # Practice group users cannot change practice groups.
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( $existingPracticeGroupsUser->getValue( 'user_id' ) != $this->getValue('user_id' ) ) {
                # Practice group users can only change user ids if they are changing from 0, and that is explicitly
                # permitted at the top of this function.
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( !$existingPracticeGroupsUser->getValue( 'admin' ) && $this->getValue('admin' )
                && !$myPracticeGroupsUser->isAdmin() ) {
                # The user being edited is changing to admin but the editing user is not an admin.
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( $existingPracticeGroupsUser->getValue( 'admin' ) && !$this->getValue('admin' ) ) {
                # The user being edited is changing to not admin.
                if( !$myPracticeGroupsUser->isAdmin() ) {
                    # The editing user is not an admin
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( count( $practiceGroup->getAdminPracticeGroupsUsers() ) <= 1 ) {
                    # The last admin cannot be demoted.
                    $result->fatal( 'practicegroups-error-cantremovelastadmin' );

                    return $result;
                }
            }

            if( !$existingPracticeGroupsUser->getValue( 'active_since' ) && $this->getValue('active_since' ) ) {
                if( !$practiceGroup->canJoinByPublic() ) {
                    if( $existingPracticeGroupsUser->getValue( 'invited_since' ) && $user->getId() != $myUser->getId() ) {
                        # The user being edited is invited but the requesting user does not match the user being edited
                        # Invitations can only be self-accepted
                        $result->fatal( $genericErrorMessage );

                        return $result;
                    } elseif( $existingPracticeGroupsUser->getValue( 'requested_since' ) ) {
                        # The user has a pending request which is being approved
                        if( !$myPracticeGroupsUser->canAddUser() ) {
                            # The editing user does not have permissions to add users to the practice group
                            $result->fatal( $genericErrorMessage );

                            return $result;
                        } elseif( $this->getValue( 'approved_by_user_id' ) != $myUser->getId() ) {
                            # The editing user did not declare themself as the approving user
                            $result->fatal( $genericErrorMessage );

                            return $result;
                        }
                    } elseif( $existingPracticeGroupsUser->getValue( 'awaiting_email_verification_since' ) ) {
                        # Email verification has a special case to escape permissions checks above, so we don't need to check for that here.
                        $result->fatal( $genericErrorMessage );

                        return $result;
                    }
                }
            }

            if( $existingPracticeGroupsUser->getValue( 'active_since' ) && !$this->getValue('active_since' ) ) {
                # Nonsensical case.
                # Users can never change to inactive (they should just be deleted).
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( $existingPracticeGroupsUser->getValue( 'invited_since' ) != $this->getValue('invited_since' ) ) {
                # Nonsensical case.
                # Users should never change have their invited timestamp changed after creation
                # (i.e. it is not cleared upon acceptance).
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( $existingPracticeGroupsUser->getValue( 'requested_since' ) != $this->getValue('requested_since' ) ) {
                # Nonsensical case.
                # Users should never change have their requested timestamp changed after creation
                # (i.e. it is not cleared upon approval).
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( $existingPracticeGroupsUser->getValue( 'awaiting_email_verification_since' ) != $this->getValue('awaiting_email_verification_since' ) ) {
                # Nonsensical case.
                # Users should never change have their awaiting email verification timestamp changed after creation
                # (i.e. it is not cleared upon verification).
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( $existingPracticeGroupsUser->getValue( 'affiliated_email' ) != $this->getValue('affiliated_email' ) ) {
                if( $practiceGroup->getPracticeGroupsUserForEmail( $this->getValue( 'affiliated_email' ) ) ) {
                    # Email is not unique in the practice group
                    $result->fatal( 'practicegroups-error-practicegroupsuser-emailalreadyexists' );

                    return $result;
                }
            }


            if( $existingPracticeGroupsUser->getValue( 'email_verification_code' ) != $this->getValue('email_verification_code' ) ) {
                # The email verification code can only be changed after creation by verifyAffiliatedEmailAddress(),
                # but that has a special case above and won't make it this far, so there's no valid circumstance for this to happen.
                $result->fatal( $genericErrorMessage );

                return $result;
            }

            if( !$existingPracticeGroupsUser->getValue( 'approved_by_user_id' ) && $this->getValue('approved_by_user_id' ) ) {
                # The user being edited is being approved

                if( $this->getValue('approved_by_user_id' ) != $myUser->getId() ) {
                    # The approving user does not match the editing user
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( !$myPracticeGroupsUser->canAddUser() ) {
                    # The editing user does not have permissions to approve users
                    $result->fatal( $genericErrorMessage );

                    return $result;
                } elseif( $existingPracticeGroupsUser->getValue( 'active_since' ) || !$this->getValue( 'active_since' ) ) {
                    # `active_since did not also change from 0.
                    # (if this was a valid invite, $action would be 'create' not 'edit')
                    $result->fatal( $genericErrorMessage );

                    return $result;
                }
            }
        } elseif( $action === static::ACTION_DELETE ) {
            # The valid cases for deletion are:
            # - A user deletes themselves from a practice group
            # - A user is deleted from a practice group by a practice group administrator
            # - A pending user is deleted from a practice group by an active member (if allowed by practice group)

            # TODO handle the case where a non-registered wiki user gets invited and wants to cancel their own invite.

            if( !$myPracticeGroupsUser ) {
                # The deleting user does not have a practice group user.
                $result->fatal( $genericErrorMessage );

                return $result;
            } elseif( $user->getId() != $myUser->getId() ) {
                # The user is not deleting themself.

                if( !$myPracticeGroupsUser->isAdmin() ) {
                    # The deleting user is not an admin

                    if( $this->isActive() ) {
                        # The user being deleted is an active user
                        $result->fatal( $genericErrorMessage );

                        return $result;
                    } elseif( !$myPracticeGroupsUser->canAddUser() ) {
                        # The user being deleted is pending, but the deleting user cannot manage membership requests
                        $result->fatal( $genericErrorMessage );

                        return $result;
                    }
                }
            } elseif( $this->isAdmin() && count( $practiceGroup->getAdminPracticeGroupsUsers() ) <= 1 ) {
                # The last admin cannot delete themself.
                $result->fatal( $genericErrorMessage );

                return $result;
            }
        }

        return $result;
    }

    public function isActive() {
        if( $this->exists() && $this->getValue( 'active_since' ) > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function isAdmin() {
        if( $this->isActive() && $this->getValue( 'admin' ) ) {
            return true;
        } else {
            return false;
        }
    }

    public function isAwaitingEmailVerification() {
        if( $this->exists() && $this->getValue( 'awaiting_email_verification_since' ) > 0 && $this->getValue( 'active_since' ) == 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function isInvited() {
        if( $this->exists() && $this->getValue( 'invited_since' ) > 0 && $this->getValue( 'active_since' ) == 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function isPending() {
        if( $this->exists() && $this->getValue( 'active_since' ) == 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function isRequested() {
        if( $this->exists() && $this->getValue( 'requested_since' ) > 0 && $this->getValue( 'active_since' ) == 0 ) {
            return true;
        } else {
            return false;
        }
    }

    public function save( bool $test = false ): Status {
        $result = Status::newGood();

        $practiceGroup = $this->getPracticeGroup();

        if( !$practiceGroup ) {
            $result->fatal( 'practicegroups-error-doesnotexist' );

            return $result;
        }

        $dbObject = static::getFromId( $this->getId() );

        # Check permissions
        if( !$dbObject ) {
            $action = 'create';
        } else {
            $action = 'edit';
        }

        # Track whether the user is being activated to run a hook upon successful save
        $practiceGroupUserActivating = false;

        # hasRight() will do thorough checks to make sure all the data is valid. This only cleans up the object
        # to populate fields in certain conditions and make a few choice validations that require a more specific
        # error message if they fail.
        if( $action === static::ACTION_CREATE ) {
            if( count( $practiceGroup->getAllPracticeGroupsUsers() ) == 0 ) {
                # Special case, if the practice group has no users, force the creating user to be an admin
                $this->setValue( 'admin', 1 );
                $this->setValue( 'active_since', time() );
            }

            if( $this->getValue( 'active_since' ) > 0 ) {
                # If it's set, reset it to the server time.
                $this->setValue( 'active_since', time() );

                # The only valid case where a user being created is set to active is self-creation for a practice group
                # that can be joined by the public. hasRight() will assert the proper permissions, but we should make
                # sure that approved_by_user_id is also set.
                $this->setValue( 'approved_by_user_id', $this->getValue( 'user_id' ) );

                $practiceGroupUserActivating = true;
            }

            if( $this->getValue( 'invited_since' ) > 0 ) {
                # If it's set, reset it to the server time.
                $this->setValue( 'invited_since', time() );
            }

            if( $this->getValue( 'requested_since' ) > 0 ) {
                # If it's set, reset it to the server time.
                $this->setValue( 'requested_since', time() );
            } else {
                $this->setValue( 'request_reason', '' );
            }

            if( $this->getValue( 'awaiting_email_verification_since' ) > 0 ) {
                $this->setValue( 'awaiting_email_verification_since', time() );

                if( $practiceGroup->canJoinByRequest() ) {
                    $this->setValue(
                        'requested_since',
                        $this->getValue( 'awaiting_email_verification_since' )
                    );
                }
            }

            if( $this->getValue( 'affiliated_email' ) ) {
                $resultEmailValidation = $this->validateAffiliatedEmail();

                if( !$resultEmailValidation->isOK() ) {
                    return $resultEmailValidation;
                }

                if( $this->getValue( 'awaiting_email_verification_since' )
                    || ( $this->getValue( 'invited_since' ) && $this->getValue( 'user_id' ) == 0 ) ) {
                    $this->setValue( 'email_verification_code', static::generateRandomString(
                        static::getMaxLength( 'email_verification_code' )
                    ) );
                }
            }
        } elseif( $action === static::ACTION_EDIT ) {
            if( $this->getValue( 'active_since' ) && !$dbObject->getValue( 'active_since' ) ) {
                $this->setValue( 'active_since', time() );

                $practiceGroupUserActivating = true;
            }
        }

        $result = parent::save( $test );

        # Unset flag set when a practice group email is being verified which can bypass some checks of hasRight()
        # before calling any post-save operations.
        $this->affiliatedEmailVerified = false;

        if( !$result->isOK() ) {
            return $result;
        }

        if( $action === 'create' ) {
            if( $this->isInvited() ) {
                $resultSendEmail = $this->sendInvitationEmail();

                if( !$resultSendEmail->isOK() ) {
                    return $resultSendEmail;
                }
            } elseif( $this->isAwaitingEmailVerification() ) {
                $resultSendEmail = $this->sendVerificationEmail();

                if( !$resultSendEmail->isOK() ) {
                    return $resultSendEmail;
                }
            }
        }

        if( $practiceGroupUserActivating ) {
            Hooks::run( 'PracticeGroupUserActivated', [ $this ] );
        }

        return $result;
    }


    public function sendInvitationEmail(): Status {
        global $wgPasswordSender, $wgNoReplyAddress, $wgServer, $wgSitename;

        $result = Status::newGood();

        $practiceGroup = $this->getPracticeGroup();
        $user = $this->getUser();

        if( !$practiceGroup
            || ( $user->getId() > 0 && !$user->loadFromDatabase() ) ) {
            $result->fatal( 'practicegroups-error-couldnotsendinvitationemail' );

            return $result;
        }

        if( !$this->isInvited() || $this->isActive() ) {
            $result->fatal( 'practicegroups-error-couldnotsendinvitationemail' );

            return $result;
        }

        $invitingUser = $this->getApprovedByUser();

        if( !$invitingUser->loadFromDatabase() ) {
            $result->fatal( 'practicegroups-error-couldnotsendinvitationemail' );

            return $result;
        }

        if( $user->getId() > 0 ) {
            $toAddress = new MailAddress(
                $user->getEmail(),
                $user->getName(),
                $user->getRealName() );

            $body = wfMessage(
                'practicegroups-email-invitation-body',
                $user->getRealName() ? $user->getRealName() : $user->getName(),
                $invitingUser->getRealName() ? $invitingUser->getRealName() : $invitingUser->getName(),
                (string) $practiceGroup,
                $wgSitename,
                Title::newFromText( 'Special:PracticeGroups' )->getFullURL()
            )->text();
        } elseif( $this->getAffiliatedEmail() ) {
            $toAddress = new MailAddress( $this->getAffiliatedEmail() );

            $invitationData = [
                'id' => $this->getId(),
                'code' => $this->getValue( 'email_verification_code' )
            ];

            $invitationId = base64_encode( serialize( $invitationData ) );

            $body = wfMessage(
                'practicegroups-email-invitation-body-anonymous',
                $invitingUser->getRealName() ? $invitingUser->getRealName() : $invitingUser->getName(),
                (string) $practiceGroup,
                $wgSitename,
                Title::newFromText( 'Special:CreateAccount' )->getFullURL() . '?pgdata=' . $invitationId,
                $practiceGroup->getShortName(),
                Title::newFromText( 'Special:PracticeGroups' )->getFullURL() . '/' . $practiceGroup->getDBKey() . '?pgdata=' . $invitationId,
            )->text();
        } else {
            $result->fatal( 'practicegroups-error-couldnotsendverificationemail' );

            return $result;
        }

        $body .= wfMessage( 'practicegroups-email-signature', $wgSitename )->text();

        $fromAddress = new MailAddress(
            $wgPasswordSender,
            wfMessage( 'emailsender' )->inContentLanguage()->text()
        );

        $replyToAddress = new MailAddress( $wgNoReplyAddress );

        $subject = wfMessage( 'practicegroups-email-invitation-subject', $practiceGroup->getShortName() )->text();

        $options = [
            'contentType' => 'text/html',
            'replyTo' => $replyToAddress
        ];

        return UserMailer::send( $toAddress, $fromAddress, $subject, $body, $options );
    }



    public function sendVerificationEmail(): Status {
        global $wgPasswordSender, $wgNoReplyAddress, $wgServer, $wgSitename;

        $result = Status::newGood();

        $practiceGroup = $this->getPracticeGroup();
        $user = $this->getUser();

        if( !$practiceGroup
            || !$user->loadFromDatabase() ) {
            $result->fatal( 'practicegroups-error-couldnotsendverificationemail' );

            return $result;
        }

        if( !$this->isAwaitingEmailVerification() || $this->isActive()
            || !$this->getValue( 'email_verification_code' ) ) {
            $result->fatal( 'practicegroups-error-notawaitingemailverification' );

            return $result;
        }

        $toEmail = $this->getAffiliatedEmail();

        if( !$toEmail ) {
            $result->fatal( 'practicegroups-error-couldnotsendverificationemail' );

            return $result;
        }

        # Need to revalidate because the definitions of the affiliated domains could've changed.
        $emailValidationResult = $this->validateAffiliatedEmail();

        if( !$emailValidationResult->isOK() ) {
            # If email is no longer valid, delete the practice group user
            $this->delete();

            return $emailValidationResult;
        }

        $toAddress = new MailAddress(
            $toEmail,
            $user->getName(),
            $user->getRealName()
        );

        $fromAddress = new MailAddress(
            $wgPasswordSender,
            wfMessage( 'emailsender' )->inContentLanguage()->text()
        );

        $replyToAddress = new MailAddress( $wgNoReplyAddress );

        $subject = wfMessage( 'practicegroups-email-verification-subject' )->text();

        $verificationData = [
            'id' => $this->getId(),
            'code' => $this->getValue( 'email_verification_code' )
        ];

        $verificationId = base64_encode( serialize( $verificationData ) );

        $body = wfMessage(
            'practicegroups-email-verification-body',
            (string) $practiceGroup,
            $wgServer,
            $wgSitename,
            Title::newFromText( 'Special:PracticeGroups' )->getFullURL() . '/' . $practiceGroup->getDBKey() . '?pgdata=' . $verificationId,
            $practiceGroup->getShortName()
        )->text();

        $body .= wfMessage( 'practicegroups-email-signature', $wgSitename );

        $options = [
            'contentType' => 'text/html',
            'replyTo' => $replyToAddress
        ];

        return UserMailer::send( $toAddress, $fromAddress, $subject, $body, $options );
    }



    public function verifyAffiliatedEmail( string $testVerificationCode, int $verifyingUserId = 0 ): Status {
        $result = Status::newGood();

        $practiceGroup = $this->getPracticeGroup();
        $user = $this->getUser();

        # Do any actual validation from fresh values from the database in case the user is doing anything sketchy.
        $dbPracticeGroupsUser = static::getFromId( $this->getId() );

        if( !$practiceGroup
            || !$dbPracticeGroupsUser ) {
            $result->fatal(
                'practicegroups-error-couldnotactivatepracticegroupuser',
                wfMessage( 'practicegroups-error-requireddatamissing' )->text()
            );

            return $result;
        }

        if( !$dbPracticeGroupsUser->isAwaitingEmailVerification()
            && ( !$dbPracticeGroupsUser->isInvited() || $dbPracticeGroupsUser->getUserId() != 0 ) ) {
            $result->fatal(
                'practicegroups-error-couldnotactivatepracticegroupuser',
                wfMessage( 'practicegroups-error-requireddatamissing' )->text()
            );

            return $result;
        }

        # Only validate the email if the user is awaiting verification
        if( $dbPracticeGroupsUser->isAwaitingEmailVerification() ) {
            $emailValidationResult = $dbPracticeGroupsUser->validateAffiliatedEmail();

            if( !$emailValidationResult->isOK() ) {
                # If email is no longer valid, delete the practice group user
                $this->delete();

                return $emailValidationResult;
            }
        }

        if( $testVerificationCode != $dbPracticeGroupsUser->getValue( 'email_verification_code' ) ) {
            $result->fatal(
                'practicegroups-error-couldnotactivatepracticegroupuser',
                wfMessage( 'practicegroups-error-invalidverificationcode' )->text()
            );

            return $result;
        }

        $userId = $dbPracticeGroupsUser->getValue( 'user_id' );

        if( !$userId ) {
            if( $verifyingUserId ) {
                $userId = $verifyingUserId;
            } else {
                $userId = RequestContext::getMain()->getUser()->getId();
            }
        }

        if( $userId != $dbPracticeGroupsUser->getValue( 'user_id' ) ) {
            # Existing user id will be 0. Need to make sure the user id we're about to assign isn't
            # already a member of the practice group
            if( $practiceGroup->getPracticeGroupsUserForUser( $userId ) ) {
                $result->fatal( 'practicegroups-error-practicegroupsuser-alreadyexists' );

                return $result;
            }
        }

        # This flag will bypass usual permissions checks in hasRight() when calling save().
        # Since this variable is private, we can't just do it to $dbPracticeGroupsUser.
        # save() will unset this flag immediately after saving before any cleanup is called.
        $this->affiliatedEmailVerified = true;

        # We are also going to overwrite all other values in this object with the database values in case the
        # user is up to any funny business.
        $this->setValues( [
            'user_id' => $userId,
            'admin' => $dbPracticeGroupsUser->getValue( 'admin' ),
            'active_since' => time(),
            'invited_since' => $dbPracticeGroupsUser->getValue( 'invited_since' ),
            'requested_since' => $dbPracticeGroupsUser->getValue( 'requested_since' ),
            'request_reason' => $dbPracticeGroupsUser->getValue( 'request_reason' ),
            'awaiting_email_verification_since' => $dbPracticeGroupsUser->getValue( 'awaiting_email_verification_since' ),
            'affiliated_email' => $dbPracticeGroupsUser->getValue( 'affiliated_email' ),
            'email_verification_code' => '',
            'approved_by_user_id' => $user->getId(),
            'display_order' => $dbPracticeGroupsUser->getValue( 'display_order' ),
        ] );

        $result = $this->save();

        return $result;
    }



    public function validateAffiliatedEmail(): Status {
        $result = Status::newGood();

        $practiceGroup = $this->getPracticeGroup();

        if( !$practiceGroup ) {
            $result->fatal(
                'practicegroups-notfound',
                wfMessage( 'practicegroups-practicegroup')->text()
            );

            return $result;
        }

        $userEmail = filter_var( $this->getAffiliatedEmail(), FILTER_VALIDATE_EMAIL );

        if( !$userEmail ) {
            $result->fatal( 'practicegroups-error-emailnotvalid' );

            return $result;
        }

        # Only check the domain if the user is awaiting email verification.
        # This function is also called to validate the user's email in general for sending out an invitation.
        if( $this->isAwaitingEmailVerification() ) {
            return PracticeGroups::validateAffiliatedEmail( $practiceGroup, $userEmail );
        }

        return $result;
    }
}