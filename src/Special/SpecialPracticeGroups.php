<?php

namespace PracticeGroups\Special;

use BootstrapUI\AlertWidget;
use BootstrapUI\BootstrapUI;
use Html;
use Linker;
use MediaWiki\MediaWikiServices;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\PracticeGroups;
use PracticeGroups\DatabaseClass\PracticeGroupsUser;
use PracticeGroups\Form\PracticeGroupFormEdit;
use PracticeGroups\Form\PracticeGroupFormJoinRequest;
use Title;
use SpecialPage;
use User;

class SpecialPracticeGroups extends SpecialPage {

    public function __construct() {
        parent::__construct( 'PracticeGroups' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $this->outputHeader();

        $out = $this->getOutput();

        $out->addModules( [
            'ext.practiceGroups.special',
        ] );

        # $this->addTestData(); return;

        if( $subPage ) {
            $this->executePracticeGroup( $subPage );
        } else {
            $this->executeMain();
        }

        PracticeGroups::wrapRenderShield( $out );
    }

    private function executeMain() {
        $req = $this->getRequest();
        $action = trim( $req->getText( 'action' ) );


        if( $action == 'create' ) {
            $this->showCreateForm();
        } else {
            $this->showMain();
        }
    }

    private function executePracticeGroup( string $dbKey ) {
        $out = $this->getOutput();
        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

        $out->setSubtitle( wfMessage( 'backlinksubtitle' )
            ->rawParams( $linkRenderer->makeLink( Title::newFromText( 'Special:PracticeGroups' ), wfMessage( 'practicegroups-breadcrumb' )->text() ) ) );

        if( !$dbKey ) {
            $out->addHTML(
                BootstrapUI::alertWidget( [
                    'alertStyle' => BootstrapUI::ALERT_STYLE_DANGER
                ], wfMessage( 'practicegroups-error-dbkeynotdefined' )->text() )
            );

            return;
        }

        $practiceGroup = PracticeGroup::getFromDBKey( $dbKey );

        if( !$practiceGroup ) {
            $out->addHTML(
                BootstrapUI::alertWidget( [
                    'alertStyle' => BootstrapUI::ALERT_STYLE_DANGER
                ], wfMessage( 'practicegroups-error-practicegroup-notfound', $dbKey )->text() )
            );

            return;
        }

        $req = $this->getRequest();
        $action = trim( $req->getText( 'action' ) );

        if( $action == 'request' ) {
            $this->showJoinRequestForm( $practiceGroup );
        } elseif( $req->getText( 'pgdata' ) ) {
            $verificationId = @unserialize( base64_decode( trim( $req->getText( 'pgdata' ) ) ) );

            if( !is_array( $verificationId ) || !isset( $verificationId[ 'id' ] ) || !isset( $verificationId[ 'code' ] ) ) {
                $out->addHTML(
                    BootstrapUI::alertWidget( [
                        'alertStyle' => BootstrapUI::ALERT_STYLE_DANGER
                    ], wfMessage( 'practicegroups-error-couldnotactivatepracticegroupuser',
                        wfMessage( 'practicegroups-error-requireddatamissing' )->text()
                    )->text()
                    )
                );

                return;
            }

            $practiceGroupsUserId = $verificationId[ 'id' ];
            $verificationCode = $verificationId[ 'code' ];

            $practiceGroupsUser = PracticeGroupsUser::getFromId( $practiceGroupsUserId );

            if( !$practiceGroupsUser ) {
                $out->addHTML(
                    BootstrapUI::alertWidget( [
                        'alertStyle' => BootstrapUI::ALERT_STYLE_DANGER
                    ], wfMessage( 'practicegroups-error-couldnotactivatepracticegroupuser',
                        wfMessage( 'practicegroups-error-practicegroupsuser-notfound' )->text()
                    )->text()
                    )
                );

                return;
            }

            $this->requireLogin();

            if( $practiceGroupsUser->isActive() ) {
                $out->addHTML(
                    BootstrapUI::alertWidget( [
                            'alertStyle' => BootstrapUI::ALERT_STYLE_INFO
                        ], wfMessage( 'practicegroups-verifyemail-alreadyactive' )->text()
                    )
                );

                return;
            }

            $verifyResult = $practiceGroupsUser->verifyAffiliatedEmail( $verificationCode );

            if( !$verifyResult->isOK() ) {
                $out->addHTML(
                    BootstrapUI::alertWidget( [
                        'alertStyle' => BootstrapUI::ALERT_STYLE_DANGER
                    ], wfMessage( 'practicegroups-error-couldnotactivatepracticegroupuser',
                        $verifyResult->getMessage()->text()
                    )->text()
                    )
                );

                return;
            }

            $out->addHTML(
                BootstrapUI::alertWidget( [
                    'alertStyle' => BootstrapUI::ALERT_STYLE_SUCCESS
                ], wfMessage( 'practicegroups-success-practicegroupuser-activated',
                        (string) $practiceGroup
                    )->text()
                )
            );
        }
    }
    
    private function getAllPracticeGroupsHtml() {
        $html = '';

        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

        $allPracticeGroups = PracticeGroup::getAll();

        $html .= Html::element( 'h4', [], wfMessage( 'practicegroups-allpracticegroups' )->text() );

        if( empty( $allPracticeGroups ) ) {
            $html .= wfMessage( 'practicegroups-noallpracticegroups' )->text();
        } else {
            $this->getOutput()->addModules( 'ext.practiceGroups.dataTables' );

            $html .= Html::openElement( 'div', [
                'class' => 'table-responsive mb-3'
            ] );

            $html .= Html::openElement( 'table', [
                'class' => 'table table-sm',
                'id' => 'table-allpracticegroups'
            ] );

            $html .= Html::openElement( 'thead' );

            $html .= Html::rawElement( 'tr', [],
                Html::rawElement('th', [], wfMessage( 'practicegroups-practicegroup' )->text() ) .
                Html::rawElement('th', [], wfMessage( 'practicegroups-members' )->text() ) .
                Html::rawElement('th', [], wfMessage( 'practicegroups-articles' )->text() ) .
                Html::rawElement('th', [], wfMessage( 'practicegroups-membershippolicy' )->text() ) .
                Html::rawElement('th', [], '&nbsp;' )
            );

            $html .= Html::closeElement( 'thead' );

            $html .= Html::openElement( 'tbody' );

            $tdAttribs = [
                'class' => 'align-middle'
            ];

            $tdButtonsAttribs = $tdAttribs;
            $tdButtonsAttribs[ 'class' ] .= ' practicegroups-table-buttons-1';

            foreach( $allPracticeGroups as $practiceGroup ) {
                $html .= Html::openElement( 'tr' );

                $nameHtml = (string) $practiceGroup;

                if( $this->getUser()->isRegistered() && $practiceGroup->canUserView( $this->getUser()->getId() ) ) {
                    # Show name as link
                    $nameHtml = $linkRenderer->makeKnownLink( $practiceGroup->getDashboardTitle(), (string) $practiceGroup );
                }

                $membershipPolicyDetailsHtml = '';

                if( $practiceGroup->canJoinByPublic() ) {
                    $membershipPolicyDetailsHtml .= wfMessage( 'practicegroups-membershippolicy-public' )->text();
                } else {
                    $membershipPolicyDetailsHtml .= wfMessage( 'practicegroups-membershippolicy-private' )->text();
                }

                $membershipPolicyDetailsHtml .= ' ' . BootstrapUI::collapseWidget( [
                    'class' => 'p-3',
                    'linkContents' => wfMessage( 'practicegroups-membershippolicy-details' )->text()
                ], PracticeGroups::getMembershipPolicyDetailsHtml( $practiceGroup ) );

                $buttons = '';

                if( $this->getUser()->isRegistered()
                    && !$practiceGroup->getPracticeGroupsUserForUser( $this->getUser()->getId() ) ) {
                    if( $practiceGroup->canJoinByPublic() ) {
                        $buttons .= PracticeGroups::getMembershipButtonHtml( 'join', 0, $practiceGroup->getId() );
                    } elseif( $practiceGroup->canJoinByRequest()
                        || $practiceGroup->canJoinByAffiliatedEmail() ) {
                        $buttons .= BootstrapUI::buttonWidget( [
                            'class' => 'bs-ui-buttonHideLabelMobile practicegroup-joinrequest-button',
                            'href' => $this->getPageTitle()->getLinkURL() . '/' . $practiceGroup->getDBKey() . '?action=request',
                            'icon' => 'fas fa-sign-in-alt fa-fw',
                            'label' => wfMessage( 'practicegroups-practicegroup-joinrequest-button' )->text()
                        ] );
                    }
                }

                $html .= Html::rawElement( 'td', $tdAttribs, $nameHtml );
                $html .= Html::rawElement( 'td', $tdAttribs, count( $practiceGroup->getActivePracticeGroupsUsers() ) );
                $html .= Html::rawElement( 'td', $tdAttribs, count( $practiceGroup->getArticles() ) );
                $html .= Html::rawElement( 'td', $tdAttribs, $membershipPolicyDetailsHtml );
                $html .= Html::rawElement( 'td', $tdButtonsAttribs, $buttons );

                $html .= Html::closeElement( 'tr' );
            }

            $html .= Html::closeElement( 'tbody' );

            $html .= Html::closeElement( 'table' );
            $html .= Html::closeElement( 'div' );
        }

        return $html;
    }

    private function getCreatePracticeGroupHtml() {
        $html = '';

        $html .= Html::rawElement( 'h4', [], wfMessage( 'practicegroups-createnew' )->text() );

        $html .= Html::openElement( 'div', [ 'class' => 'mb-3' ] );

        $html .= Html::rawElement( 'p', [], wfMessage( 'practicegroups-createnew-description' )->parse() );

        if( PracticeGroup::hasRightGeneric( 'create' ) ) {
            $html .= BootstrapUI::buttonWidget( [
                'class' => 'practicegroup-create-button',
                'href' => $this->getPageTitle()->getLinkURL() . '?action=create',
                'icon' => 'fas fa-plus',
                'label' => wfMessage( 'practicegroups-createnew' )->text()
            ] );
        } else {
            $html .= wfMessage( 'practicegroups-nopermission-create' )->parse();
        }

        $html .= Html::element( 'hr' );

        $html .= Html::closeElement( 'div' );
        
        return $html;
    }

    private function getMyPracticeGroupsHtml() {
        $html = '';

        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

        $myPracticeGroupsUsers = PracticeGroupsUser::getAllForUser( $this->getUser()->getId() );

        $html .= Html::element( 'h4', [], wfMessage( 'practicegroups-mypracticegroups' )->text() );

        if( !$this->getUser()->isRegistered() ) {
            $html .= wfMessage(
                'practicegroups-loginformypracticegroups',
                $linkRenderer->makeLink( Title::newFromText( 'Special:UserLogin' ), wfMessage( 'practicegroups-login' )->text(), [], [ 'returnto' => $this->getPageTitle()->getFullText() ] )
            )->text();
        } elseif( empty( $myPracticeGroupsUsers ) ) {
            $html .= wfMessage( 'practicegroups-nomypracticegroups' )->text();
        } else {
            $html .= Html::openElement( 'table', [
                'class' => 'table'
            ] );

            foreach( $myPracticeGroupsUsers as $myPracticeGroupsUser ) {
                $practiceGroup = $myPracticeGroupsUser->getPracticeGroup();

                $html .= Html::openElement( 'tr' );

                $nameHtml = '';
                $statusText = '';
                $buttons = '';

                if( $myPracticeGroupsUser->isActive() ) {
                    # User is an active member

                    # Show name as link
                    $nameHtml = $linkRenderer->makeKnownLink( $practiceGroup->getDashboardTitle(), (string) $practiceGroup );

                    $buttons .= PracticeGroups::getMembershipButtonHtml( 'leave', $myPracticeGroupsUser->getId(), $practiceGroup->getId() );
                } else {
                    # Show name as plain text
                    $nameHtml = (string) $practiceGroup;

                    if( $myPracticeGroupsUser->isInvited() ) {
                        # User has been invited but has not yet accepted
                        $invitingUser = $myPracticeGroupsUser->getApprovedByUser();

                        if( $invitingUser ) {
                            $statusText = wfMessage( 'practicegroups-practicegroup-status-invitationpendingfromuser',
                                Linker::userLink( $invitingUser->getId(), $invitingUser->getName(), $invitingUser->getRealName() )
                            )->text();
                        } else {
                            $statusText = wfMessage( 'practicegroups-practicegroup-status-invitationpending' )->text();
                        }

                        $buttons .= PracticeGroups::getMembershipButtonHtml( 'acceptinvitation', $myPracticeGroupsUser->getId(), $practiceGroup->getId() );
                        $buttons .= PracticeGroups::getMembershipButtonHtml( 'declineinvitation', $myPracticeGroupsUser->getId(), $practiceGroup->getId() );
                    } else {
                        if( $practiceGroup->canJoinByAffiliatedEmail()
                            && $myPracticeGroupsUser->getValue( 'affiliated_email' ) ) {
                            # Awaiting email confirmation
                            $statusText = wfMessage( 'practicegroups-practicegroup-status-awaitingemailverification' )->text();

                            $buttons .= PracticeGroups::getMembershipButtonHtml( 'resendemail', $myPracticeGroupsUser->getId(), $practiceGroup->getId() );
                        } else {
                            # User has requested to join and is awaiting approval
                            $statusText = wfMessage( 'practicegroups-practicegroup-status-awaitingapproval' )->text();
                        }

                        $buttons .= PracticeGroups::getMembershipButtonHtml( 'cancelrequest', $myPracticeGroupsUser->getId(), $practiceGroup->getId() );
                    }
                }

                if( $statusText ) {
                    $nameHtml .= '<br />' . Html::rawElement( 'i', [], $statusText );
                }

                $practiceGroupNameAttribs = [
                    'class' => 'align-middle'
                ];

                if( !$buttons ) {
                    $practiceGroupNameAttribs[ 'colspan' ] = 2;
                }

                $html .= Html::rawElement( 'td', $practiceGroupNameAttribs, $nameHtml );

                if( $buttons ) {
                    $buttons = BootstrapUI::buttonGroupWidget( [], $buttons );

                    $buttonsAttribs = [
                        'class' => 'align-middle practicegroups-table-buttons-2'
                    ];

                    $html .= Html::rawElement( 'td', $buttonsAttribs, $buttons );
                }

                $html .= Html::closeElement( 'tr' );
            }

            $html .= Html::closeElement( 'table' );
        }

        $html .= Html::element( 'hr', [
            'style' => 'margin-top: -1rem;'
        ] );

        return $html;
    }

    private function showMain( AlertWidget $alert = null ) {
        $out = $this->getOutput();

        $html = '';

        if( $alert ) {
            $html .= $alert->getHtml();
        }

        $html .= $this->getMyPracticeGroupsHtml();
        $html .= $this->getCreatePracticeGroupHtml();
        $html .= $this->getAllPracticeGroupsHtml();

        $out->addHTML( $html );
    }

    private function showCreateForm() {
        $out = $this->getOutput();
        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

        if( !$this->getUser()->isRegistered() ) {
            $this->requireLogin();
        } elseif( !PracticeGroup::hasRightGeneric( 'create' ) ) {
            $errorHtml = BootstrapUI::alertWidget( [
                'alertStyle' => BootstrapUI::ALERT_STYLE_DANGER
            ], wfMessage( 'practicegroups-error-permissiondenied' )->text() );

            $out->addHTML( $errorHtml );

            return;
        }

        $out->setPageTitle( wfMessage( 'practicegroups-createnew' )->text() );

        $out->setSubtitle( wfMessage( 'backlinksubtitle' )
            ->rawParams( $linkRenderer->makeLink( $this->getPageTitle(), wfMessage( 'practicegroups-action' )->text() ) ) );

        $html = PracticeGroupFormEdit::getHtml();

        $out->addHTML( $html );
    }

    private function showJoinRequestForm( PracticeGroup $practiceGroup ) {
        $out = $this->getOutput();

        if( !$this->getUser()->isRegistered() ) {
            $this->requireLogin();
        } elseif( $practiceGroup->getPracticeGroupsUserForUser( $this->getUser()->getId() ) ) {
            $errorHtml = BootstrapUI::alertWidget( [
                'alertStyle' => BootstrapUI::ALERT_STYLE_DANGER
            ], wfMessage( 'practicegroups-form-joinrequest-error-existinguser' )->text() );

            $out->addHTML( $errorHtml );

            return;
        } elseif( !$practiceGroup->canJoinByRequest() && !$practiceGroup->canJoinByAffiliatedEmail() ) {
            $errorHtml = BootstrapUI::alertWidget( [
                'alertStyle' => BootstrapUI::ALERT_STYLE_DANGER
            ], wfMessage( 'practicegroups-error-permissiondenied' )->text() );

            $out->addHTML( $errorHtml );

            return;
        }

        $out->setPageTitle( wfMessage( 'practicegroups-form-joinrequest-pagetitle', (string) $practiceGroup ) );

        $html = PracticeGroupFormJoinRequest::getHtml( $practiceGroup );

        $out->addHTML( $html );
    }

    private function addTestData() {

        $this->requireLogin();

        $practiceGroup = PracticeGroup::newFromValues( [
            'dbkey' => 'Stanford',
            'name_full' => 'Stanford University',
            'name_short' => 'Stanford',
            'view_by_public' => 0,
            'join_by_public' => 0,
            'join_by_request' => 1,
            'any_member_add_user' => 1,
            'join_by_affiliated_email' => 1,
            'affiliated_domains' => 'stanford.edu,stanfordhealthcare.org,stanfordchildrens.org'
        ] );

        $practiceGroup->save();


        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => $this->getUser()->getId()
        ] );

        # Should succeed
        $result = $practiceGroupsUser->save(); if( !$result->isOK() ) { var_dump( $result ); }

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => User::newFromName( 'Test10' )->getId(),
            'awaiting_email_verification_since' => time(),
            'affiliated_email' => 'rishel@stanford.edu'
        ] );

        # Should fail
        $result = $practiceGroupsUser->save(); if( !$result->isOK() ) { var_dump( $result ); }

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => User::newFromName( 'Test8' )->getId(),
            'active_since' => time()
        ] );

        # Should succeed
        $result = $practiceGroupsUser->save(); if( !$result->isOK() ) { var_dump( $result ); }

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => User::newFromName( 'Test9' )->getId(),
            'requested_since' => time(),
            'request_reason' => 'Pleaseeeeee'
        ] );

        # Should fail
        $result = $practiceGroupsUser->save(); if( !$result->isOK() ) { var_dump( $result ); }

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => User::newFromName( 'Test10' )->getId(),
            'requested_since' => time(),
            'affiliated_email' => 'rishel@gmail.com'
        ] );

        # Should fail
        $result = $practiceGroupsUser->save(); if( !$result->isOK() ) { var_dump( $result ); }

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => User::newFromName( 'Test7' )->getId(),
            'admin' => 1,
            'active_since' => time()
        ] );

        # Should succeed
        $result = $practiceGroupsUser->save(); if( !$result->isOK() ) { var_dump( $result ); }

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => User::newFromName( 'Test6' )->getId(),
            'invited_since' => time(),
            'approved_by_user_id' => $this->getUser()->getId()
        ] );

        # Should succeed
        $result = $practiceGroupsUser->save(); if( !$result->isOK() ) { var_dump( $result ); }




        $practiceGroup = PracticeGroup::newFromValues( [
            'dbkey' => 'Hopkins',
            'name_full' => 'Johns Hopkins University',
            'name_short' => 'Hopkins',
            'view_by_public' => 0,
            'join_by_public' => 0,
            'join_by_request' => 0,
            'any_member_add_user' => 1,
            'join_by_affiliated_email' => 1,
            'affiliated_domains' => 'jhmi.edu'
        ] );

        $practiceGroup->save();

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => $this->getUser()->getId(),
            'requested_since' => time(),
            'affiliated_email' => 'rishel@gmail.com',
            'email_verification_code' => 'ksGSoGij298uNFjX'
        ] );

        // $practiceGroupsUser->save();




        $practiceGroup = PracticeGroup::newFromValues( [
            'dbkey' => 'Dartmouth',
            'name_full' => 'Dartmouth-Hitchcock Medical Center',
            'name_short' => 'Dartmouth',
            'view_by_public' => 1,
            'join_by_public' => 1,
            'join_by_request' => 0,
            'any_member_add_user' => 1,
            'join_by_affiliated_email' => 0,
            'affiliated_domains' => 'hitchcock.org'
        ] );

        $practiceGroup->save();




        $practiceGroup = PracticeGroup::newFromValues( [
            'dbkey' => 'MGH',
            'name_full' => 'Massachusetts General Hospital',
            'name_short' => 'MGH',
            'view_by_public' => 0,
            'join_by_public' => 0,
            'join_by_request' => 0,
            'any_member_add_user' => 1,
            'join_by_affiliated_email' => 0,
            'affiliated_domains' => 'mgh.harvard.edu'
        ] );

        $practiceGroup->save();

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => $this->getUser()->getId(),
            'invited_since' => time(),
            'approved_by_user_id' => 4
        ] );

        //$practiceGroupsUser->save();




        $practiceGroup = PracticeGroup::newFromValues( [
            'dbkey' => 'UCLA',
            'name_full' => 'University of California, Los Angeles',
            'name_short' => 'UCLA',
            'view_by_public' => 0,
            'join_by_public' => 0,
            'join_by_request' => 1,
            'any_member_add_user' => 0,
            'join_by_affiliated_email' => 0,
            'affiliated_domains' => 'ucla.edu'
        ] );

        $practiceGroup->save();

        $practiceGroupsUser = PracticeGroupsUser::newFromValues( [
            'practicegroup_id' => $practiceGroup->getId(),
            'user_id' => $this->getUser()->getId(),
            'requested_since' => time()
        ] );

        //$practiceGroupsUser->save();




        $practiceGroup = PracticeGroup::newFromValues( [
            'dbkey' => 'UCSF',
            'name_full' => 'University of California, San Francisco',
            'name_short' => 'UCSF',
            'view_by_public' => 1,
            'join_by_public' => 0,
            'join_by_request' => 1,
            'any_member_add_user' => 1,
            'join_by_affiliated_email' => 1,
            'affiliated_domains' => 'ucsf.edu'
        ] );

        $practiceGroup->save();
    }
}