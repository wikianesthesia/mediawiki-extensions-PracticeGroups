<?php

namespace PracticeGroups\Auth;

use MediaWiki\Auth\AuthenticationRequest;
use RequestContext;

class PracticeGroupsAuthenticationRequest extends AuthenticationRequest {
    /** @var string */
    public $pgdata;

    public function getFieldInfo() {
        # Detailed css for these fields is set using the hook AuthChangeFormFields

        $req = RequestContext::getMain()->getRequest();

        $pgdataValue = $req->getText( 'pgdata' ) ? $req->getText( 'pgdata' ) : '';

        # Encoded practice groups data (for accepting invitations)
        $pgdata = [
            'type' => 'hidden',
            'optional' => true,
            'value' => $pgdataValue
        ];

        return [
            'pgdata' => $pgdata
        ];
    }
}