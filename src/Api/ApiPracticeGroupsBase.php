<?php


namespace PracticeGroups\Api;

use ApiBase;

abstract class ApiPracticeGroupsBase extends ApiBase {

    /**
     * @var ApiPracticeGroups
     */
    protected $apiPracticeGroups;

    /**
     * @param ApiPracticeGroups $api
     * @param string $modName
     * @param string $prefix
     */
    public function __construct( ApiPracticeGroups $api, string $modName, string $prefix = '' ) {
        $this->apiPracticeGroups = $api;

        parent::__construct( $api->getMain(), $modName, $prefix );
    }

    /**
     * Return the name of the practice groups action
     * @return string
     */
    abstract protected function getAction();

    /**
     * @inheritDoc
     */
    public function needsToken() {
        return 'csrf';
    }

    /**
     * @inheritDoc
     */
    public function getParent() {
        return $this->apiPracticeGroups;
    }

    public function simplifyError( string $error ) {
        return explode(':', $error )[ 0 ];
    }
}