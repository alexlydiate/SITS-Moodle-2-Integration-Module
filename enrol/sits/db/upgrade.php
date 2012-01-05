<?php
function xmldb_block_sits_upgrade($oldversion = 0) {

    $result = true;

    /// Add a new column newcol to the mdl_question_myqtype
    if ($result && $oldversion < 2011050502) {
        /// Define table sits_mappings_history to be created
        $table = new XMLDBTable('sits_mappings_history');

        /// Adding fields to table sits_mappings_history
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('map_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('action', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('method', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('end_date', XMLDB_TYPE_CHAR, '25', null, null, null, null, null, null);
        $table->addFieldInfo('timestamp', XMLDB_TYPE_CHAR, '25', null, null, null, null, null, null);

        /// Adding keys to table sits_mappings_history
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table for sits_mappings_history
        $result = $result && create_table($table);

        /// Define table sits_period to be created
        $table = new XMLDBTable('sits_period');

        /// Adding fields to table sits_period
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('period_code', XMLDB_TYPE_CHAR, '6', null, null, null, null, null, null);
        $table->addFieldInfo('acyear', XMLDB_TYPE_CHAR, '6', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('start_date', XMLDB_TYPE_CHAR, '25', null, null, null, null, null, null);
        $table->addFieldInfo('end_date', XMLDB_TYPE_CHAR, '25', null, null, null, null, null, null);
        $table->addFieldInfo('revert', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timestamp', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

        /// Adding keys to table sits_period
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table for sits_period
        $result = $result && create_table($table);
        /// sits_mappings alteration
        /// Define field active to be added to sits_mappings
        $table = new XMLDBTable('sits_mappings');
        $field = new XMLDBField('active');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'manual');

        /// Launch add field active
        $result = $result && add_field($table, $field);

        /// Define field start_date to be dropped from sits_mappings
        $table = new XMLDBTable('sits_mappings');
        $field = new XMLDBField('timestamp');

        /// Launch drop field start_date
        $result = $result && drop_field($table, $field);
    }

    return $result;
}
