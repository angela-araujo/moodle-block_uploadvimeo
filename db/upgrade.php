<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

function xmldb_block_uploadvimeo_upgrade($oldversion) {

    global $DB;

    $dbman = $DB->get_manager();

    $newversion = 2021061500;

    if ($oldversion < $newversion) {

        /**
         * Step 1. Create table account vimeo.
         */

        // Define new table to be created.
        $table = new xmldb_table('block_uploadvimeo_account');

        // Define fields to be added to table block_uploadvimeo_account.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('clientid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'name');
        $table->add_field('clientsecret', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'clientid');
        $table->add_field('accesstoken', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'clientsecret');
        $table->add_field('app_id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, '-1', 'accesstoken');
        $table->add_field('status', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, null, 'app_id');

        // Adding keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes.
        $table->add_index('unique', XMLDB_INDEX_UNIQUE, ['name']);

        // Create in db if not exists.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        /**
         * Step 2. Insert data from config into table block_uploadvimeo_account.
         */
        // Get current config.
        $config = get_config('block_uploadvimeo');

        $record = new stdClass();
        $record->name = 'Main account Vimeo';
        $record->clientid = $config->config_clientid;
        $record->clientsecret = $config->config_clientsecret;
        $record->accesstoken = $config->config_accesstoken;
        $record->status = 1; // 0-Inactive; 1-Active

        $accountid = $DB->insert_record('block_uploadvimeo_account', $record);

        // Set new config.
        set_config('accountvimeo', $accountid, 'block_uploadvimeo');

        // Insert others clientid
        $sql = "SELECT DISTINCT f.clientid
                  FROM {block_uploadvimeo_folders} f
                 WHERE f.clientid <> :oldclientid ";

        $params = array('oldclientid' => $config->config_clientid);

        $list_account = array();

        $list_account[$record->clientid] = $accountid;

        if ($others_accounts = $DB->get_records_sql($sql, $params)) {

            foreach ($others_accounts as $account) {

                $newobject = new stdClass();
                $newobject->name = 'Other account ' . $account->clientid;
                $newobject->clientid = $account->clientid;
                $newobject->clientsecret = -1;
                $newobject->accesstoken = -1;
                $newobject->status = 0;

                $newobjectid = $DB->insert_record('block_uploadvimeo_account', $newobject);
                $list_account[$newobject->clientid] = $newobjectid;
            }
        }

        /**
         * Step 3. Add new field accountid in table block_uploadvimeo_folders.
         */

        $table = new xmldb_table('block_uploadvimeo_folders');
        $field = new xmldb_field('accountid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, -1, 'userid');

        // Conditionally launch add field accountid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        /**
         *
         */
        $index = new xmldb_index('mdl_blocuplofold_cliuse_uix', XMLDB_INDEX_UNIQUE, ['clientid', 'userid']);
        // Conditionally launch drop field clientid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('foldername', XMLDB_INDEX_NOTUNIQUE, ['foldername']);
        // Conditionally launch drop field clientid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rename field foldername on table block_uploadvimeo_folders to foldernamevimeo.
        $field = new xmldb_field('foldername', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'accountid');

        // Launch rename field foldernamevimeo.
        $dbman->rename_field($table, $field, 'foldernamevimeo');

        $index = new xmldb_index('foldernamevimeo', XMLDB_INDEX_NOTUNIQUE, ['foldernamevimeo']);
        // Conditionally launch drop field clientid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Rename field foldername on table block_uploadvimeo_folders to foldernamevimeo.
        $field = new xmldb_field('folderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'foldernamevimeo');

        // Launch rename field foldernamevimeo.
        $dbman->rename_field($table, $field, 'folderidvimeo');

        /**
         * Step 4. Insert value in new field accountid from table block_uploadvimeo_folders.
         */

        $recordset = $DB->get_recordset('block_uploadvimeo_folders');

        foreach ($recordset as $folder) {
            $newfolder = new stdClass();
            $newfolder->id = $folder->id;
            $newfolder->userid = $folder->userid;
            $newfolder->accountid = $list_account[$folder->clientid];
            $newfolder->foldernamevimeo = $folder->foldernamevimeo;
            $newfolder->folderid = $folder->folderid;
            $newfolder->timecreated = $folder->timecreated;
            $newfolder->timecreatedvimeo = $folder->timecreatedvimeo;
            $DB->update_record('block_uploadvimeo_folders', $newfolder);
        }
        $recordset->close();

        /**
         * Step 5. Drop index 'foldernameunique' and 'vimeokey' from folders.
         */

        $index = new xmldb_index('foldernameunique');

        // Conditionally launch drop field clientid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('vimeokey');

        // Conditionally launch drop field clientid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        /**
         * Step 6. Re create index foldernameunique from folders.
         */

        $index = new xmldb_index('uniquefolder', XMLDB_INDEX_UNIQUE, ['accountid', 'userid']);

        // Conditionally launch add index foldername.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }


        /**
         * Step 7. Drop old field clientid from folders.
         */

        $field = new xmldb_field('clientid');

        // Conditionally launch drop field clientid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        /**
         * Step 8. Create new table block_upload_videos.
         */

        // Define table block_uploadvimeo_videos to be created.
        $table = new xmldb_table('block_uploadvimeo_videos');

        // Adding fields to table block_uploadvimeo_videos.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('accountid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, -1);
        $table->add_field('folderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, -1);
        $table->add_field('videoidvimeo', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('videonamevimeo', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('linkvideo', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('linkpicture', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('size_bytes', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('quality', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreatedvimeo', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_uploadvimeo_videos.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table block_uploadvimeo_videos.
        $table->add_index('unique', XMLDB_INDEX_UNIQUE, ['accountid', 'videoidvimeo']);
        $table->add_index('videonamevimeo', XMLDB_INDEX_NOTUNIQUE, ['videonamevimeo']);

        // Conditionally launch create table for block_uploadvimeo_videos.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        /**
         * Step 9. Unset olds config from block_uploadvimeo.
         */

        unset_config('clientid', 'block_uploadvimeo');
        unset_config('clientsecret', 'block_uploadvimeo');
        unset_config('accesstoken', 'block_uploadvimeo');

        // Uploadvimeo savepoint reached.
        upgrade_block_savepoint(true, $newversion, 'uploadvimeo');
    }

    if ($oldversion < 2021102800) {

        // Define table block_uploadvimeo_zoom to be created.
        $table = new xmldb_table('block_uploadvimeo_zoom');

        // Adding fields to table block_uploadvimeo_zoom.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('zoomid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_uploadvimeo_zoom.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('zoomid', XMLDB_KEY_FOREIGN, ['zoomid'], 'zoom', ['id']);

        // Adding indexes to table block_uploadvimeo_zoom.
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for block_uploadvimeo_zoom.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Uploadvimeo savepoint reached.
        upgrade_block_savepoint(true, 2021102800, 'uploadvimeo');
    }

    if ($oldversion < 2021111800) {

        // Define field hasrecordings to be added to block_uploadvimeo_zoom.
        $table = new xmldb_table('block_uploadvimeo_zoom');
        $field = new xmldb_field('hasrecordings', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timecreated');

        // Conditionally launch add field hasrecordings.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index hasrecordings (not unique) to be added to block_uploadvimeo_zoom.
        $index = new xmldb_index('hasrecordings', XMLDB_INDEX_NOTUNIQUE, ['hasrecordings']);

        // Conditionally launch add index hasrecordings.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Uploadvimeo savepoint reached.
        upgrade_block_savepoint(true, 2021111800, 'uploadvimeo');
    }

    if ($oldversion < 2021113000) {

        $table = new xmldb_table('block_uploadvimeo_zoom');

        // Define index hasrecordings (not unique) to be dropped form block_uploadvimeo_zoom.
        $index = new xmldb_index('hasrecordings', XMLDB_INDEX_NOTUNIQUE, ['hasrecordings']);

        // Conditionally launch drop index timecreated.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field hasrecordings to be dropped from block_uploadvimeo_zoom.
        $field = new xmldb_field('hasrecordings');

        // Conditionally launch drop field hasrecordings.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field recordingid to be added to block_uploadvimeo_zoom.
        $field = new xmldb_field('recordingid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'timecreated');

        // Conditionally launch add field recordingid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field vimeouri to be added to block_uploadvimeo_zoom.
        $field = new xmldb_field('vimeouri', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'recordingid');

        // Conditionally launch add field vimeouri.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field vimeocompleted to be added to block_uploadvimeo_zoom.
        $field = new xmldb_field('vimeocompleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'vimeouri');

        // Conditionally launch add field vimeocompleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Uploadvimeo savepoint reached.
        upgrade_block_savepoint(true, 2021113000, 'uploadvimeo');
    }

    if ($oldversion < 2021120600) {

        // Rename field vimeouri on table block_uploadvimeo_zoom to NEWNAMEGOESHERE.
        $table = new xmldb_table('block_uploadvimeo_zoom');
        $field = new xmldb_field('vimeouri', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'recordingid');

        // Launch rename field vimeouri to vimevideoid.
        $dbman->rename_field($table, $field, 'vimeovideoid');

        // Uploadvimeo savepoint reached.
        upgrade_block_savepoint(true, 2021120600, 'uploadvimeo');
    }

    if ($oldversion < 2021121500) {

        // Define key zoomid_recordingid (unique) to be added to block_uploadvimeo_zoom.
        $table = new xmldb_table('block_uploadvimeo_zoom');
        $key = new xmldb_key('zoomid_recordingid', XMLDB_KEY_UNIQUE, ['zoomid', 'recordingid']);

        // Launch add key zoomid_recordingid.
        $dbman->add_key($table, $key);

        // Uploadvimeo savepoint reached.
        upgrade_block_savepoint(true, 2021121500, 'uploadvimeo');
    }


    return true;
}
