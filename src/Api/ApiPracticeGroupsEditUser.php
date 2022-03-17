<?php


namespace PracticeGroups\Api;

use ApiBase;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\DatabaseClass\PracticeGroupsUser;
use RequestContext;
use Title;
use User;

class ApiPracticeGroupsEditUser extends ApiPracticeGroupsBasePost {
    public function __construct( $api, $modName ) {
        parent::__construct( $api, $modName, '' );
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        $pgaction = $this->getAction();
        $params = $this->extractRequestParams();

        $output = [ $pgaction => [
            'result' => [],
            'status' => 'ok',
        ] ];

        $myUser = RequestContext::getMain()->getUser();

        if( !$params[ 'user_id' ] ) {
            # User id is only not defined when inviting a user to a practice group. We can try to find the user id
            # in several ways.
            if( $params[ 'user_name' ] ) {
                $testTitle = Title::makeTitleSafe( NS_USER, $params[ 'user_name' ] );

                if( $testTitle === null ) {
                    $output[ $pgaction ][ 'status' ] = 'error';
                    $output[ $pgaction ][ 'message' ] = wfMessage( 'practicegroups-error-invalid-emailorusername', $params[ 'user_name' ] )->escaped();

                    $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );

                    return;
                }

                $userId = User::idFromName( $params[ 'user_name' ] );

                if( !$userId ) {
                    $output[ $pgaction ][ 'status' ] = 'error';
                    $output[ $pgaction ][ 'message' ] = wfMessage( 'practicegroups-error-usernotfound', $params[ 'user_name' ] )->escaped();

                    $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );

                    return;
                }

                $params[ 'user_id' ] = $userId;
            } elseif( $params[ 'affiliated_email' ] ) {
                # This should work fine to produce a user id, but since email addresses aren't necessarily unique,
                # it's hard to know which user to pick and thus I'm not sure we should.
                /*
                $userId = PracticeGroups::getUserIdForEmail( $params[ 'affiliated_email' ] );

                if( $userId ) {
                    $params[ 'user_id' ] = $userId;
                }
                */
            }
        }

        $practiceGroupsUserId = $params[ 'practicegroupsuser_id' ];

        if( !$practiceGroupsUserId ) {
            # Creating a new practicegroupsuser
            $practiceGroup = PracticeGroup::getFromId( $params[ 'practicegroup_id' ] );

            if( !$practiceGroup ) {
                $output[ $pgaction ][ 'status' ] = 'error';
                $output[ $pgaction ][ 'message' ] = wfMessage( 'practicegroups-error-practicegroups-notfound', $params[ 'practicegroup_id' ] )->text();

                $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );

                return;
            }

            # Validation will be handled in save()
            if( $params[ 'useraction' ] === 'inviteuser' ) {
                $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
                    'practicegroup_id' => $practiceGroup->getId(),
                    'user_id' => $params[ 'user_id' ],
                    'invited_since' => time(),
                    'affiliated_email' => $params[ 'affiliated_email' ],
                    'approved_by_user_id' => $myUser->getId()
                ] );
            } elseif( $params[ 'useraction' ] === 'join' ) {
                $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
                    'practicegroup_id' => $practiceGroup->getId(),
                    'user_id' => $myUser->getId(),
                    'active_since' => time(),
                    'approved_by_user_id' => $myUser->getId()
                ] );
            } elseif( $params[ 'useraction' ] === 'joinrequest' ) {
                $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
                    'practicegroup_id' => $practiceGroup->getId(),
                    'user_id' => $params[ 'user_id' ],
                    'requested_since' => time()
                ] );

                if( $params[ 'affiliated_email' ] ) {
                    $practiceGroupsUser->setValues( [
                        'awaiting_email_verification_since' => time(),
                        'affiliated_email' => $params[ 'affiliated_email' ]
                    ] );
                }

                if( $params[ 'requested_since' ] ) {
                    $practiceGroupsUser->setValues( [
                        'requested_since' => time(),
                        'request_reason' => $params[ 'request_reason' ]
                    ] );
                }
            } else {
                $output[ $pgaction ][ 'status' ] = 'error';
                $output[ $pgaction ][ 'message' ] = wfMessage( 'practicegroups-error-practicegroupsuser-invaliduseraction', 'create', $params[ 'useraction' ] )->text();

                $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );

                return;
            }
        } else {
            # If we're editing, we should start with the database values and only overwrite values we're explicitly given.
            $practiceGroupsUser = PracticeGroupsUser::getFromId( $practiceGroupsUserId );

            if( !$practiceGroupsUser ) {
                $output[ $pgaction ][ 'status' ] = 'error';
                $output[ $pgaction ][ 'message' ] = wfMessage( 'practicegroups-error-practicegroupsuser-notfound' )->text();

                $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );

                return;
            }

            if( $params[ 'useraction' ] === 'acceptinvitation' ) {
                $practiceGroupsUser->setValues( [
                    'active_since' => time()
                ] );
            } elseif( $params[ 'useraction' ] === 'approverequest' ) {
                $practiceGroupsUser->setValues( [
                    'active_since' => time(),
                    'approved_by_user_id' => $myUser->getId()
                ] );
            } elseif( $params[ 'useraction' ] === 'demoteadmin' ) {
                $practiceGroupsUser->setValues( [
                    'admin' => 0
                ] );
            } elseif( $params[ 'useraction' ] === 'promoteadmin' ) {
                $practiceGroupsUser->setValues( [
                    'admin' => 1
                ] );
            } else {
                $output[ $pgaction ][ 'status' ] = 'error';
                $output[ $pgaction ][ 'message' ] = wfMessage( 'practicegroups-error-practicegroupsuser-invaliduseraction', 'edit', $params[ 'useraction' ] )->text();

                $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );

                return;
            }
        }

        $result = $practiceGroupsUser->save();

        if( !$result->isOK() ) {
            $output[ $pgaction ][ 'status' ] = 'error';
            $output[ $pgaction ][ 'message' ] = $this->simplifyError( $result->getMessage()->text() );

            $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );

            return;
        }

        $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );
    }

    /**
     * @inheritDoc
     */
    protected function getAction() {
        return 'edituser';
    }

    public function getAllowedParams() {
        return [
            'useraction' => [
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_TYPE => [
                    'acceptinvitation',
                    'approverequest',
                    'demoteadmin',
                    'inviteuser',
                    'join',
                    'joinrequest',
                    'promoteadmin'
                ]
            ],
            'practicegroupsuser_id' => [
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'practicegroup_id' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'user_id' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'user_name' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'admin' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'active_since' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'invited_since' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'requested_since' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'request_reason' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'awaiting_email_verification_since' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'affiliated_email' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'approved_by_user_id' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'display_order' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ]
        ];
    }
}