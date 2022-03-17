<?php


namespace PracticeGroups\Api;

use ApiBase;
use PracticeGroups\DatabaseClass\PracticeGroupsUser;

class ApiPracticeGroupsRemoveUser extends ApiPracticeGroupsBasePost {
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

        $practiceGroupsUser = PracticeGroupsUser::getFromId( $params[ 'practicegroupsuser_id' ] );

        if( !$practiceGroupsUser ) {
            $output[ $pgaction ][ 'status' ] = 'error';
            $output[ $pgaction ][ 'message' ] = wfMessage( 'practicegroups-error-notfound', wfMessage( 'practicegroups-practicegroupuser' )->text() )->text();

            $this->getResult()->addValue( null, $this->apiPracticeGroups->getModuleName(), $output );

            return;
        }

        $result = $practiceGroupsUser->delete();

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
        return 'removeuser';
    }

    public function getAllowedParams() {
        return [
            'useraction' => [
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_TYPE => [
                    'cancelinvitation',
                    'cancelrequest',
                    'declineinvitation',
                    'leave',
                    'rejectrequest',
                    'removeuser'
                ]
            ],
            'practicegroupsuser_id' => [
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_TYPE => 'integer'
            ]
        ];
    }
}