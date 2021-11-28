<?php


namespace PracticeGroups\Api;


abstract class ApiPracticeGroupsBaseGet extends ApiPracticeGroupsBase {

    /**
     * @inheritDoc
     */
    public function mustBePosted() {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function needsToken() {
        return false;
    }
}