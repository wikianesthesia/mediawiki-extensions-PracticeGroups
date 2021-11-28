<?php


namespace PracticeGroups\Form;

use BootstrapUI\BootstrapUI;
use Html;
use PracticeGroups\DatabaseClass\PracticeGroup;
use PracticeGroups\DatabaseClass\PracticeGroupsUser;
use PracticeGroups\PracticeGroups;
use RequestContext;

class PracticeGroupFormJoinRequest extends PracticeGroupForm {

    protected static function getFormDatabaseClass(): string {
        return PracticeGroup::class;
    }

    protected static function getFormId(): string {
        return 'form-joinrequest';
    }

    public static function getHtml( PracticeGroup $practiceGroup = null ): string {
        $html = '';

        if( !$practiceGroup ) {
            return $html;
        }

        $out = RequestContext::getMain()->getOutput();
        $user = RequestContext::getMain()->getUser();

        $out->addModules( 'ext.practiceGroups.formJoinRequest' );

        $html .= Html::openElement( 'form', [
            'id' => static::getFormId(),
            'novalidate' => ''
        ] );

        $name = 'practicegroup_id';
        $html .= Html::openElement( 'input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => $practiceGroup->getId(),
            'id' => static::getElementIdForName( $name )
        ] );

        $name = 'user_id';
        $html .= Html::openElement( 'input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => $user->getId(),
            'id' => static::getElementIdForName( $name )
        ] );

        $name = 'practicegroup_name';
        $html .= Html::openElement( 'input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => (string) $practiceGroup,
            'id' => static::getElementIdForName( $name )
        ] );

        if( $practiceGroup->canJoinByRequest() ) {
             $joinByRequestText = wfMessage( 'practicegroups-form-joinrequest-joinbyrequest' )->text();

            if( $practiceGroup->canJoinByAffiliatedEmail() ) {
                $joinByRequestText .= '<br/><br/>' . wfMessage( 'practicegroups-form-joinrequest-joinbyrequestandemail' )->text();
            }

            $html .= BootstrapUI::alertWidget( [
                'alertStyle' => BootstrapUI::ALERT_STYLE_INFO,
                'dismissible' => true,
                'class' => 'mt-3'
            ], $joinByRequestText );
        }

        if( $practiceGroup->canJoinByAffiliatedEmail() ) {
            $affiliatedDomains = implode(',', $practiceGroup->getAffiliatedDomains() );

            $name = 'affiliated_domains';
            $html .= Html::openElement( 'input', [
                'type' => 'hidden',
                'name' => $name,
                'value' => $affiliatedDomains,
                'id' => static::getElementIdForName( $name )
            ] );

            $affiliatedEmail = '';

            if( $user->getEmail() && PracticeGroups::validateAffiliatedEmail( $practiceGroup, $user->getEmail() )->isOK() ) {
                $affiliatedEmail = $user->getEmail();
            }

            $name = 'affiliated_email';
            $html .= BootstrapUI::textInputWidget( [
                'name' => $name,
                'value' => $affiliatedEmail,
                'type' => 'email',
                'maxlength' => PracticeGroupsUser::getMaxLength( $name ),
                'required' => !$practiceGroup->canJoinByRequest(),
                'validation' => true,
                'id' => static::getElementIdForName( $name ),
                'label' => static::getMessageText( $name, 'label' ) .
                    ( $practiceGroup->canJoinByRequest() ? ' (' . wfMessage( 'practicegroups-optional' )->text() . ')' : '' ),
                'help' => static::getMessageText( $name, 'help',
                    str_replace( ',', ', ', $affiliatedDomains ) ),
                'labelClass' => 'mt-3'
            ] );
        }

        if( $practiceGroup->canJoinByRequest() ) {
            $name = 'request_reason';
            $html .= BootstrapUI::textInputWidget( [
                'name' => $name,
                'type' => 'text',
                'maxlength' => PracticeGroupsUser::getMaxLength( $name ),
                'id' => static::getElementIdForName( $name ),
                'label' => static::getMessageText( $name, 'label' ),
                'help' => static::getMessageText( $name, 'help' )
            ] );
        }

        $html .= Html::openElement( 'div', [
            'class' => 'mb-3'
        ] );

        $name = 'submit';
        $elementId = static::getElementIdForName( $name );
        $labelText = static::getMessageText( $name, 'label' );
        $html .= BootstrapUI::buttonWidget( [
            'id' => $elementId,
            'label' => $labelText
        ] );

        $html .= Html::closeElement( 'div' );

        $html .= Html::closeElement( 'form' );

        return $html;
    }
}