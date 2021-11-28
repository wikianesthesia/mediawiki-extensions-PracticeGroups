<?php


namespace PracticeGroups\Hook;

use CommentStoreComment;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Revision\SlotRecord;
use PracticeGroups\PracticeGroups;
use Status;
use User;
use WikitextContent;

class MultiContentSave {
    public static function callback( RenderedRevision $renderedRevision, User $user, CommentStoreComment $summary, $flags, Status $hookStatus ) {
        $revision = $renderedRevision->getRevision();
        $title = $revision->getPageAsLinkTarget();

        if( !PracticeGroups::isTitlePracticeGroupArticle( $title ) ) {
            return;
        }

        static::stripCategories( $renderedRevision );
    }

    public static function stripCategories( RenderedRevision $renderedRevision ) {
        global $wgPracticeGroupsDisableCategories;

        if( !$wgPracticeGroupsDisableCategories ) {
            return;
        }

        $reCategory = '/[\s]*\[\[category:.*?\]\][\s]*/mi';

        $revision = $renderedRevision->getRevision();
        $title = $revision->getPageAsLinkTarget();
        $slots = $revision->getSlots();
        $content = $slots->getContent( SlotRecord::MAIN );
        $text = $content->getText();

        $strippedText = preg_replace( $reCategory, '', $text );

        if( $text != $strippedText ) {
            $strippedContent = new WikitextContent( $strippedText );
            $slots->setContent( SlotRecord::MAIN, $strippedContent );
            $renderedRevision->setRevisionParserOutput( $strippedContent->getParserOutput( $title ) );
        }
    }
}