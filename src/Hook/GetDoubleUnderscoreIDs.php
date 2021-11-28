<?php

namespace PracticeGroups\Hook;

class GetDoubleUnderscoreIDs {
    public static function callback( array &$mDoubleUnderscoreIDs ) {
        $mDoubleUnderscoreIDs[] = 'nopracticegroups';
    }
}