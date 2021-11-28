<?php


namespace PracticeGroups\Api;

use ApiBase;
use ApiModuleManager;
use MediaWiki\MediaWikiServices;

class ApiPracticeGroups extends ApiBase {

    /**
     * @var ApiModuleManager
     */
    private $moduleManager;

    private static $pgActions = [
        // POST
        'edit' => \PracticeGroups\Api\ApiPracticeGroupsEdit::class,
        'edituser' => \PracticeGroups\Api\ApiPracticeGroupsEditUser::class,
        'removeuser' => \PracticeGroups\Api\ApiPracticeGroupsRemoveUser::class,

        // GET
        'editvalidate' => \PracticeGroups\Api\ApiPracticeGroupsEditValidate::class,
        'resendverificationemail' => \PracticeGroups\Api\ApiPracticeGroupsResendVerificationEmail::class
    ];

    public function __construct( $main, $action ) {
        parent::__construct( $main, $action );

        $this->moduleManager = new ApiModuleManager(
            $this,
            MediaWikiServices::getInstance()->getObjectFactory()
        );

        $this->moduleManager->addModules( self::$pgActions, 'pgaction' );
    }

    public function getModuleManager() {
        return $this->moduleManager;
    }

    public function execute() {
        $this->getMain()->getVal( '_' );

        $params = $this->extractRequestParams();

        /** @var $module ApiPracticeGroupsBase */
        $module = $this->moduleManager->getModule( $params[ 'pgaction' ], 'pgaction' );

        // The checks for POST and tokens are the same as ApiMain.php
        $wasPosted = $this->getRequest()->wasPosted();
        if ( !$wasPosted && $module->mustBePosted() ) {
            $this->dieWithErrorOrDebug( [ 'apierror-mustbeposted', $params[ 'pgaction' ] ] );
        }

        if ( $module->needsToken() ) {
            if ( !isset( $params[ 'token' ] ) ) {
                $this->dieWithError( [ 'apierror-missingparam', 'token' ] );
            }

            $module->requirePostedParameters( [ 'token' ] );

            if ( !$module->validateToken( $params[ 'token' ], $params ) ) {
                $this->dieWithError( 'apierror-badtoken' );
            }
        }

        $module->extractRequestParams();
        $module->execute();
    }

    /**
     * @inheritDoc
     */
    public function getAllowedParams() {
        return [
            'pgaction' => [
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_TYPE => 'submodule',
            ],
            'token' => ''
        ];
    }

    public function isWriteMode() {
        // We can't use extractRequestParams() here because getHelpFlags() calls this function,
        // and we'd error out because the pgaction parameter isn't set.
        $moduleName = $this->getMain()->getVal( 'pgaction' );
        $module = $this->moduleManager->getModule( $moduleName, 'pgaction' );
        return $module ? $module->isWriteMode() : false;
    }

    public function mustBePosted() {
        return false;
    }

    public function needsToken() {
        return false;
    }
}