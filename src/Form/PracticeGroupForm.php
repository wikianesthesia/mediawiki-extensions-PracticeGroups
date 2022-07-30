<?php


namespace PracticeGroups\Form;

use DatabaseClasses\DatabaseClass;


abstract class PracticeGroupForm {

    abstract protected static function getFormDatabaseClass(): string;

    abstract protected static function getFormId(): string;

    abstract public static function getHtml(): string;

    /**
     * @param string $name
     * @param string $subElement
     * @return string
     */
    protected static function getElementIdForName( string $name, string $subElement = '' ): string {
        if( !$name ) {
            return '';
        }

        $elementId = static::getFormId() . '-' . str_replace( '_', '', strtolower( $name ) );

        if( $subElement ) {
            $elementId .= '-' . $subElement;
        }

        return $elementId;
    }


    /**
     * @param string $name
     * @param string $subElement
     * @param mixed ...$msgParams
     * @return string
     */
    protected static function getMessageParse( string $name, string $subElement = '', ...$msgParams ): string {
        $elementId = static::getElementIdForName( $name, $subElement );

        if( !$elementId ) {
            return '';
        }

        $msgKey = 'practicegroups-' . $elementId;

        return wfMessage( $msgKey, $msgParams )->parse();
    }


    /**
     * @param string $name
     * @param string $subElement
     * @param mixed ...$msgParams
     * @return string
     */
    protected static function getMessageText( string $name, string $subElement = '', ...$msgParams ): string {
        $elementId = static::getElementIdForName( $name, $subElement );

        if( !$elementId ) {
            return '';
        }

        $msgKey = 'practicegroups-' . $elementId;

        return wfMessage( $msgKey, $msgParams )->text();
    }

    /**
     * @param string $name
     * @param array $postValues
     * @param DatabaseClass|bool $databaseObject
     */
    protected static function getValue( string $name, $databaseObject ) {
        return $databaseObject ? $databaseObject->getValue( $name ) : ( static::getFormDatabaseClass() )::getDefaultValue( $name  );
    }
}