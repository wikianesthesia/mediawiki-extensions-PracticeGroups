<?php

namespace PracticeGroups\Hook;

use DatabaseUpdater;

class LoadExtensionSchemaUpdates {
    public static function callback( DatabaseUpdater $updater ) {
        # Make sure these are in the order you want them added to the database. The keys are the table names and the
        # values are any field in the table (used to see if the table is empty to insert the default data).
        $tableNames = [
            'practicegroups' => 'practicegroup_id',
            'practicegroups_page_settings' => 'practicegroups_page_setting_id',
            'practicegroups_users' => 'practicegroupsuser_id'
        ];

        $db = $updater->getDB();

        $sqlDir = __DIR__ . '/../../sql';

        # Create extension tables
        foreach( $tableNames as $tableName => $selectField) {
            if( file_exists( $sqlDir . "/tables/$tableName.sql" ) ) {
                $updater->addExtensionTable( $tableName, $sqlDir . "/tables/$tableName.sql" );

                # Import default data for tables if data exists
                if( file_exists( $sqlDir . "/data/$tableName.sql" ) ) {
                    $importTableData = false;

                    if( $updater->tableExists( $tableName ) ) {
                        $res = $db->select( $tableName, $selectField );

                        if( $res->numRows() === 0 ) {
                            $importTableData = true;
                        }
                    } else {
                        $importTableData = true;
                    }

                    if( $importTableData ) {
                        $updater->addExtensionUpdate( array( 'applyPatch', $sqlDir . "/data/$tableName.sql", true ) );
                    }
                }
            }
        }
    }
}
