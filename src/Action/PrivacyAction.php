<?php


namespace PracticeGroups\Action;

use Action;
use BootstrapUI\BootstrapUI;
use Html;
use MediaWiki\MediaWikiServices;
use PracticeGroups\DatabaseClass\PracticeGroupsPageSetting;
use PracticeGroups\PracticeGroups;
use Status;

class PrivacyAction extends Action {

    public function getName() {
        return 'privacy';
    }

    public function show() {
        // This will throw exceptions if there's a problem
        $this->checkCanExecute( $this->getUser() );

        $status = Status::newGood();
        $alert = null;

        $out = $this->getOutput();
        $out->addModules( 'ext.practiceGroups.common' );

        $user = $this->getUser();
        $title = $this->getTitle();
        $request = $this->getRequest();
        $lang = $this->getLanguage();

        $practiceGroup = PracticeGroups::getPracticeGroupFromTitle( $title );

        if( !$practiceGroup ) {
            return;
        }

        if( !$user->isRegistered() || !$practiceGroup->isUserAdmin( $user ) ) {
            return;
        }

        $privacy = PracticeGroups::getEffectivePrivacyForPage( $title->getArticleID( ) );
        $inheritedPrivacy = PracticeGroups::getEffectivePrivacyForPage( $title->getBaseTitle()->getArticleID() );

        $viewbypublic = $request->getVal('viewbypublic' );

        if( $viewbypublic !== null ) {
            // Determine if we need to write a change to the database
            if( ( $viewbypublic && $privacy != PracticeGroupsPageSetting::PRIVACY_PUBLIC ) ||
                ( !$viewbypublic && $privacy != PracticeGroupsPageSetting::PRIVACY_PRIVATE ) ) {
                // Determine the new value for privacy
                if( $viewbypublic ) {
                    // Setting the page to public
                    // If the inherited privacy is already public, set to inherit, otherwise set to public
                    $newPrivacy = $inheritedPrivacy == PracticeGroupsPageSetting::PRIVACY_PUBLIC ?
                        PracticeGroupsPageSetting::PRIVACY_INHERIT :
                        PracticeGroupsPageSetting::PRIVACY_PUBLIC;
                } else {
                    // Setting the page to private
                    // If the inherited privacy is already private, set to inherit, otherwise set to private
                    $newPrivacy = $inheritedPrivacy == PracticeGroupsPageSetting::PRIVACY_PRIVATE ?
                        PracticeGroupsPageSetting::PRIVACY_INHERIT :
                        PracticeGroupsPageSetting::PRIVACY_PRIVATE;
                }

                $practiceGroupPageSettings = PracticeGroupsPageSetting::newFromValues( [
                    'practicegroup_id' => $practiceGroup->getId(),
                    'page_id' => $title->getArticleID(),
                    'timestamp' => time(),
                    'user_id' => $user->getId(),
                    'privacy' => $newPrivacy
                ] );

                $status = $practiceGroupPageSettings->save();

                if( $status->isOK() ) {
                    $privacy = $newPrivacy;
                    $alert = wfMessage( 'practicegroups-privacy-saved' )->text();
                } else {
                    $alert = wfMessage( 'practicegroups-privacy-saved-error', $status->getHTML() )->text();
                }
            }
        }

        $viewbypublic = $privacy == PracticeGroupsPageSetting::PRIVACY_PUBLIC ? 1 : 0;

        $html = '';

        if( !$status->isOK() ) {
            $alert = $status->getHTML();
        }

        if( $alert ) {
            $html .= BootstrapUI::alertWidget( [
                'alertStyle' => $status->isOK() ? BootstrapUI::ALERT_STYLE_SUCCESS : BootstrapUI::ALERT_STYLE_DANGER,
                'dismissible' => true,
                'class' => 'mt-3'
            ], $alert );
        }

        $html .= Html::rawElement( 'h3', [],
            wfMessage( 'practicegroups-privacy-input-heading')
        );

        $html .= Html::openElement( 'form', [
            'method' => 'post'
        ] );

        $html .= BootstrapUI::radioInputWidget( [
            'name' => 'viewbypublic',
            'value' => $viewbypublic,
            'options' => [ [
                'label' => wfMessage( 'practicegroups-privacy-yes' )->text(),
                'value' => 1
            ], [
                'label' => wfMessage( 'practicegroups-privacy-no' )->text(),
                'value' => 0
            ] ],
            'required' => true,
            'validation' => true,
            'inline' => true,
            'id' => 'practicegroups-page-settings-privacy-input',
            'label' => wfMessage( 'practicegroups-privacy-input-label' )->text(),
            'help' => wfMessage( 'practicegroups-privacy-input-help', $practiceGroup->getShortName() )->parse()
        ] );

        $html .= BootstrapUI::buttonWidget( [
            'id' => 'practicegroups-privacy-submit-button',
            'label' => wfMessage( 'practicegroups-privacy-submit-button-label' )->text(),
            'type' => 'submit'
        ] );

        $html .= Html::closeElement( 'form' );


        // Get history
        // TODO make this paginated rather than one potentially long list
        $pageSettingsHistory = PracticeGroupsPageSetting::getAllForPage( $title->getArticleID() );

        if( count( $pageSettingsHistory ) ) {
            $html .= Html::rawElement( 'h3', [
                    'class' => 'mt-3'
                ],
                wfMessage( 'practicegroups-privacy-history-heading')
            );

            $html .= Html::openElement( 'div', [
                'class' => 'practicegroups-privacy-history'
            ] );

            $pageSettingsHistoryKeys = array_keys( $pageSettingsHistory );

            for( $i = 0; $i < count( $pageSettingsHistory ); $i++ ) {
                $pageSettingsChangedFrom = $i < count( $pageSettingsHistory ) - 1 ?
                    $pageSettingsHistory[ $pageSettingsHistoryKeys[ $i + 1 ] ] : null;
                $pageSettingsChangedTo = $pageSettingsHistory[ $pageSettingsHistoryKeys[ $i ] ];

                $fromPrivacy = !$pageSettingsChangedFrom || $pageSettingsChangedFrom->getPrivacy() == PracticeGroupsPageSetting::PRIVACY_INHERIT ?
                    $inheritedPrivacy : $pageSettingsChangedFrom->getPrivacy();

                $toPrivacy = $pageSettingsChangedTo->getPrivacy() == PracticeGroupsPageSetting::PRIVACY_INHERIT ?
                    $inheritedPrivacy : $pageSettingsChangedTo->getPrivacy();

                $privacyChangedFromText = PracticeGroups::getPagePrivacyText( $fromPrivacy );
                $privacyChangedToText = PracticeGroups::getPagePrivacyText( $toPrivacy );

                $html .= wfMessage(
                        'practicegroups-privacy-history-line',
                        $lang->time( $pageSettingsChangedTo->getTimestamp(), true ),
                        $lang->date( $pageSettingsChangedTo->getTimestamp(), true ) )
                        ->rawParams( MediaWikiServices::getInstance()
                            ->getLinkRenderer()->makeLink(
                                $pageSettingsChangedTo->getUser()->getUserPage(),
                                $pageSettingsChangedTo->getUser()->getRealName() ) )
                        ->params( $privacyChangedFromText, $privacyChangedToText )
                        ->escaped() . Html::rawElement( 'br' );
            }

            $html .= Html::closeElement( 'div' );
        }

        $out->addHTML( $html );
    }

    public function doesWrites() {
        return true;
    }
}
