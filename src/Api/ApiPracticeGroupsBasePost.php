<?php


namespace PracticeGroups\Api;


abstract class ApiPracticeGroupsBasePost extends ApiPracticeGroupsBase {

    /**
     * @inheritDoc
     */
    public function mustBePosted() {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isWriteMode() {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function needsToken() {
        return 'csrf';
    }
}