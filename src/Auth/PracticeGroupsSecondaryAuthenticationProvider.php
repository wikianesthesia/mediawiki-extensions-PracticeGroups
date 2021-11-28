<?php

namespace PracticeGroups\Auth;

use MediaWiki\Auth\AbstractSecondaryAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\UserDataAuthenticationRequest;
use MediaWiki\MediaWikiServices;
use PracticeGroups\DatabaseClass\PracticeGroupsUser;
use Status;

class PracticeGroupsSecondaryAuthenticationProvider extends AbstractSecondaryAuthenticationProvider {
    public function beginSecondaryAuthentication( $user, array $reqs ) {
        return AuthenticationResponse::newAbstain();
    }

    public function beginSecondaryAccountCreation( $user, $creator, array $reqs ) {
        $result = AuthenticationResponse::newAbstain();

        $req = AuthenticationRequest::getRequestByClass( $reqs, PracticeGroupsAuthenticationRequest::class );

        if( !$req ) {
            return AuthenticationResponse::newAbstain();
        }

        if( isset( $req->pgdata ) ) {
            $verificationId = @unserialize( base64_decode( trim( $req->pgdata ) ) );

            if( !is_array( $verificationId ) || !isset( $verificationId[ 'id' ] ) || !isset( $verificationId[ 'code' ] ) ) {
                return $result;
            }

            $practiceGroupsUserId = $verificationId[ 'id' ];
            $verificationCode = $verificationId[ 'code' ];

            $practiceGroupsUser = PracticeGroupsUser::getFromId( $practiceGroupsUserId );

            if( !$practiceGroupsUser ) {
                return $result;
            }

            if( !$practiceGroupsUser->isActive() ) {
                $verifyResult = $practiceGroupsUser->verifyAffiliatedEmail( $verificationCode, $user->getId() );

                if( !$verifyResult->isOK() ) {
                    return $result;
                }
            }
        }

        return AuthenticationResponse::newPass();
    }

    public function getAuthenticationRequests( $action, array $options ) {
        if( $action === AuthManager::ACTION_CREATE ) {
            return [ new PracticeGroupsAuthenticationRequest() ];
        }

        return [];
    }
}