<?php

namespace PracticeGroups\Hook;

use OutputPage;
use ParserOutput;
use PracticeGroups\PracticeGroups;

class OutputPageParserOutput {
    public static function callback( OutputPage $out, ParserOutput $parserOutput ) {
        if( $parserOutput->getProperty( 'nopracticegroups' ) === false ) {
            PracticeGroups::setTabs();
        }
    }
}