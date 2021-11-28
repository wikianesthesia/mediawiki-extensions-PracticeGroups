<?php


namespace PracticeGroups\Api;

use ApiBase;
use PracticeGroups\DatabaseClass\PracticeGroup;

class ApiPracticeGroupsEditValidate extends ApiPracticeGroupsBaseGet {
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

        $result = PracticeGroup::validateValues( $params );

        $output[ $pgaction ][ 'result' ][ 'invalidFields' ] = [];

        foreach( $result->getErrors() as $error ) {
            if( isset( $error[ 'params' ][ 0 ][ 'propertyName' ] ) ) {
                $propertyName = $error[ 'params' ][ 0 ][ 'propertyName' ];
                $errorCode = $error[ 'params' ][ 0 ][ 'error' ];
                $message = isset( $error[ 'params' ][ 0 ][ 'message' ] ) ? $error[ 'params' ][ 0 ][ 'message' ]->text() : '';

                $output[ $pgaction ][ 'result' ][ 'invalidFields' ][ $propertyName ] = [
                    'error' => $errorCode,
                    'message' => $message
                ];
            }
        }

        $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );
    }

    /**
     * @inheritDoc
     */
    protected function getAction() {
        return 'editvalidate';
    }

    public function getAllowedParams() {
        return [
            'practicegroup_id' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'integer'
            ],
            'dbkey' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'name_full' => [
                ApiBase::PARAM_REQUIRED => false,
                ApiBase::PARAM_TYPE => 'string'
            ],
            'name_short' => [
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