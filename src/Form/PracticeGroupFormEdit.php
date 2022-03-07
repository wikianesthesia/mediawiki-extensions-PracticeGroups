<?php


namespace PracticeGroups\Form;

use BootstrapUI\BootstrapUI;
use Html;
use PracticeGroups\DatabaseClass\PracticeGroup;
use RequestContext;

class PracticeGroupFormEdit extends PracticeGroupForm {


    protected static function getFormDatabaseClass(): string {
        return PracticeGroup::class;
    }

    protected static function getFormId(): string {
        return 'form-practicegroup';
    }

    public static function getHtml( PracticeGroup $practiceGroup = null ): string {
        $html = '';

        $out = RequestContext::getMain()->getOutput();

        $out->addModules( 'ext.practiceGroups.formEdit' );

        $readOnly = false;

        if( $practiceGroup ) {
            $user = $out->getUser();

            if( !$user->isRegistered() ) {
                return $html;
            }

            $myPracticeGroupsUser = $practiceGroup->getPracticeGroupsUserForUser( $user->getId() );

            if( !$myPracticeGroupsUser ) {
                return $html;
            }

            if( !$myPracticeGroupsUser->isAdmin() ) {
                $readOnly = true;
            }
        }

        if( $readOnly ) {
            $html .= BootstrapUI::alertWidget( [
                'alertStyle' => BootstrapUI::ALERT_STYLE_INFO,
                'dismissible' => true,
                'class' => 'mt-3'
            ], wfMessage( 'practicegroups-form-practicegroup-editonlyadmin' )->text() );
        }

        if( !$practiceGroup ) {
            $html .= BootstrapUI::alertWidget( [
                'alertStyle' => BootstrapUI::ALERT_STYLE_INFO,
                'dismissible' => true,
                'class' => 'mt-3'
            ], wfMessage( 'practicegroups-form-practicegroup-create-help' )->text() );
        } else {
            /*
            $name = 'delete';
            $elementId = static::getElementIdForName( $name );
            $labelText = static::getMessageText( $name, 'label' );
            $html .= BootstrapUI::buttonWidget( [
                'buttonStyle' => BootstrapUI::BUTTON_STYLE_DANGER,
                'id' => $elementId,
                'label' => $labelText
            ] );
            */
        }

        $html .= Html::openElement( 'form', [
            'id' => static::getFormId(),
            'novalidate' => ''
        ] );

        $name = 'practicegroup_id';
        if( !$readOnly ) {
            $html .= Html::openElement( 'input', [
                'type' => 'hidden',
                'name' => $name,
                'value' => $practiceGroup ? $practiceGroup->getId() : '0', // Make sure request values can't mess with this
                'id' => static::getElementIdForName( $name )
            ] );
        }

        $name = 'name_full';
        $html .= BootstrapUI::textInputWidget( [
            'name' => $name,
            'value' => static::getValue( $name, $practiceGroup ),
            'maxlength' => PracticeGroup::getMaxLength( $name ),
            'required' => true,
            'validation' => true,
            'disabled' => $readOnly,
            'readonly' => $readOnly,
            'id' => static::getElementIdForName( $name ),
            'label' => static::getMessageText( $name, 'label' ),
            'help' => !$readOnly ? static::getMessageText( $name, 'help' ) : '',
            'containerClass' => 'mt-3'
        ] );

        $name = 'name_short';
        $html .= BootstrapUI::textInputWidget( [
            'name' => $name,
            'value' => static::getValue( $name, $practiceGroup ),
            'maxlength' => PracticeGroup::getMaxLength( $name ),
            'required' => true,
            'validation' => true,
            'disabled' => $readOnly,
            'readonly' => $readOnly,
            'id' => static::getElementIdForName( $name ),
            'label' => static::getMessageText( $name, 'label' ),
            'help' => !$readOnly ? static::getMessageText( $name, 'help' ) : ''
        ] );

        $name = 'dbkey';
        if( !$readOnly ) {
            if( $practiceGroup ) {
                $html .= Html::openElement( 'input', [
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $practiceGroup->getDBKey(),
                    'id' => static::getElementIdForName( $name )
                ] );
            } else {
                $html .= BootstrapUI::textInputWidget( [
                    'name' => $name,
                    'maxlength' => PracticeGroup::getMaxLength( $name ),
                    'required' => true,
                    'validation' => true,
                    'id' => static::getElementIdForName( $name ),
                    'label' => static::getMessageText( $name, 'label' ),
                    'help' => static::getMessageText( $name, 'help' )
                ] );
            }
        }

        $name = 'privacyandmembershippolicysettings';
        $html .= Html::rawElement( 'h4', [], static::getMessageText( $name ) );

        if( !$practiceGroup ) {
            $html .= Html::rawElement( 'small', [
                'class' => 'text-muted form-heading-help'
            ], static::getMessageText( $name, 'help' ) );
        }

        $radioContainerSpacingClass = 'mt-4';

        $name = 'view_by_public';
        $html .= BootstrapUI::radioInputWidget( [
            'name' => $name,
            'value' => $practiceGroup ? (int) static::getValue( $name, $practiceGroup ) : null, // Ignore default value
            'options' => [ [
                'label' => wfMessage( 'practicegroups-yes' )->text(),
                'value' => 1
            ], [
                'label' => wfMessage( 'practicegroups-no' )->text(),
                'value' => 0
            ] ],
            'required' => true,
            'validation' => true,
            'disabled' => $readOnly,
            'id' => static::getElementIdForName( $name ),
            'label' => static::getMessageText( $name, 'label' ),
            'help' => static::getMessageText( $name, 'help' ),
            'containerClass' => 'mt-3',
            'inline' => true
        ] );

        $name = 'join_by_public';
        $html .= BootstrapUI::radioInputWidget( [
            'name' => $name,
            'value' => $practiceGroup ? (int) static::getValue( $name, $practiceGroup ) : null, // Ignore default value
            'options' => [ [
                'label' => wfMessage( 'practicegroups-yes' )->text(),
                'value' => 1
            ], [
                'label' => wfMessage( 'practicegroups-no' )->text(),
                'value' => 0
            ] ],
            'required' => true,
            'validation' => true,
            'disabled' => $readOnly,
            'id' => static::getElementIdForName( $name ),
            'label' => static::getMessageText( $name, 'label' ),
            'help' => static::getMessageText( $name, 'help' ),
            'containerClass' => $radioContainerSpacingClass,
            'inline' => true
        ] );

        $name = 'any_member_add_user';
        $html .= BootstrapUI::radioInputWidget( [
            'name' => $name,
            'value' => $practiceGroup ? (int) static::getValue( $name, $practiceGroup ) : null, // Ignore default value
            'options' => [ [
                'label' => wfMessage( 'practicegroups-yes' )->text(),
                'value' => 1
            ], [
                'label' => wfMessage( 'practicegroups-no' )->text(),
                'value' => 0
            ] ],
            'required' => true,
            'validation' => true,
            'disabled' => $readOnly,
            'id' => static::getElementIdForName( $name ),
            'label' => static::getMessageText( $name, 'label' ),
            'help' => static::getMessageText( $name, 'help' ),
            'containerClass' => $radioContainerSpacingClass . ' collapse',
            'inline' => true
        ] );

        $name = 'join_by_request';
        $html .= BootstrapUI::radioInputWidget( [
            'name' => $name,
            'value' => $practiceGroup ? (int) static::getValue( $name, $practiceGroup ) : null, // Ignore default value
            'options' => [ [
                'label' => wfMessage( 'practicegroups-yes' )->text(),
                'value' => 1
            ], [
                'label' => wfMessage( 'practicegroups-no' )->text(),
                'value' => 0
            ] ],
            'required' => true,
            'validation' => true,
            'disabled' => $readOnly,
            'id' => static::getElementIdForName( $name ),
            'label' => static::getMessageText( $name, 'label' ),
            'help' => static::getMessageText( $name, 'help' ),
            'containerClass' => $radioContainerSpacingClass . ' collapse',
            'inline' => true
        ] );

        $name = 'join_by_affiliated_email';
        $html .= BootstrapUI::radioInputWidget( [
            'name' => $name,
            'value' => $practiceGroup ? (int) static::getValue( $name, $practiceGroup ) : null, // Ignore default value
            'options' => [ [
                'label' => wfMessage( 'practicegroups-yes' )->text(),
                'value' => 1
            ], [
                'label' => wfMessage( 'practicegroups-no' )->text(),
                'value' => 0
            ] ],
            'required' => true,
            'validation' => true,
            'disabled' => $readOnly,
            'id' => static::getElementIdForName( $name ),
            'label' => static::getMessageText( $name, 'label' ),
            'help' => static::getMessageText( $name, 'help' ),
            'containerClass' => $radioContainerSpacingClass . ' collapse',
            'inline' => true
        ] );

        $name = 'affiliated_domains';
        $html .= BootstrapUI::textareaInputWidget( [
            'name' => $name,
            'value' => str_replace( ',', "\n", static::getValue( $name, $practiceGroup ) ),
            'maxlength' => PracticeGroup::getMaxLength( $name ),
            'rows' => 3,
            'required' => true,
            'validation' => true,
            'disabled' => $readOnly,
            'readonly' => $readOnly,
            'id' => static::getElementIdForName( $name ),
            'label' => static::getMessageText( $name, 'label' ),
            'help' => static::getMessageText( $name, 'help' ),
            'containerId' => static::getElementIdForName( $name , 'container' ),
            'containerClass' => $radioContainerSpacingClass . ' collapse'
        ] );

        $html .= Html::openElement( 'div', [
            'class' => 'mb-3'
        ] );

        if( !$readOnly ) {
            $name = 'save';
            $elementId = static::getElementIdForName( $name );
            $labelText = $practiceGroup ? static::getMessageText( $name, 'label' ) : wfMessage( 'practicegroups-create' )->text();
            $html .= BootstrapUI::buttonWidget( [
                'id' => $elementId,
                'label' => $labelText
            ] );
        }

        $html .= Html::closeElement( 'div' );

        $html .= Html::closeElement( 'form' );

        return $html;
    }
}