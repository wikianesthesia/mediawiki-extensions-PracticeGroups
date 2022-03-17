<?php


namespace PracticeGroups\Api;

use ApiBase;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\PracticeGroups;

class ApiPracticeGroupsEdit extends ApiPracticeGroupsBasePost {
    public function __construct( $api, $modName ) {
        parent::__construct( $api, $modName, '' );
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        $pgaction = $this->getAction();

        $output = [ $pgaction => [
            'result' => [],
            'status' => 'ok',
        ] ];

        $params = $this->extractRequestParams();

        $actionCreate = !$params[ 'practicegroup_id' ];

        $practiceGroup = PracticeGroup::newFromValues( [
            'practicegroup_id' => $params[ 'practicegroup_id' ],
            'dbkey' => $params[ 'dbkey' ],
            'name_full' => $params[ 'name_full' ],
            'name_short' => $params[ 'name_short' ],
            'color_primary' => $params[ 'color_primary' ],
            'color_secondary' => $params[ 'color_secondary' ],
            'view_by_public' => $params[ 'view_by_public' ],
            'join_by_public' => $params[ 'join_by_public' ],
            'any_member_add_user' => $params[ 'any_member_add_user' ],
            'join_by_request' => $params[ 'join_by_request' ],
            'join_by_affiliated_email' => $params[ 'join_by_affiliated_email' ],
            'affiliated_domains' => $params[ 'affiliated_domains' ]
        ] );

        $result = $practiceGroup->save();

        if( !$result->isOK() ) {
            $output[ $pgaction ][ 'status' ] = 'error';
            $output[ $pgaction ][ 'message' ] = $this->simplifyError( $result->getMessage()->text() );
        }

        $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );
    }

    /**
     * @inheritDoc
     */
    protected function getAction() {
        return 'edit';
    }

    public function getAllowedParams() {
        return [
            'practicegroup_id' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'dbkey' => [
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'name_full' => [
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'name_short' => [
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'color_primary' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'color_secondary' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'view_by_public' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'boolean'
            ],
            'join_by_public' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'boolean'
            ],
            'any_member_add_user' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'boolean'
            ],
            'join_by_request' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'boolean'
            ],
            'join_by_affiliated_email' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'boolean'
            ],
            'affiliated_domains' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'string'
            ]
        ];
    }
}