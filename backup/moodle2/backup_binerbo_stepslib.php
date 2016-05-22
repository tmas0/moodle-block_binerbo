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

/**
 * Backup class for eMail.
 *
 * @package     email
 * @copyright   adrian.castillo@uma.es
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * Proceso de backup-restauración de bloque binerbo
 */
class backup_binerbo_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        // Define each element separated.
        $binerbo = new backup_nested_element('binerbo');

        // To know if we are including userinfo
        $users = $this->get_setting_value('users');     // Con los datos de usuario.

        // All these source definitions only happen if we are including user info.
        if ($users) {
            // Define sources.
            $folders = new backup_nested_element('folders');
            $folder = new backup_nested_element('folder', array('id'),
                            array('userid', 'name', 'timecreated', 'isparenttype')
                        );
            $subfolders = new backup_nested_element('subfolders');
            $subfolder = new backup_nested_element('subfolder', array('id'),
                            array('folderparentid', 'folderchildid')
                        );

            $filters = new backup_nested_element('filters');
            $filter = new backup_nested_element('filter', array('id'),
                            array('folderid', 'rules')
                        );

            $preferences = new backup_nested_element('preferences');
            $preference = new backup_nested_element('preference', array('id'),
                            array('userid', 'trackbymail', 'marriedfolders2courses')
                        );

            $mails = new backup_nested_element('mails');
            $mail = new backup_nested_element('mail', array('id'),
                            array('userid', 'subject', 'timecreated', 'body')
                        );

            $sends = new backup_nested_element('sends');
            $send = new backup_nested_element('send', array('id'),
                            array('userid', 'mailid', 'type', 'readed', 'sended', 'answered')
                        );

            $foldermails = new backup_nested_element('foldermails');
            $foldermail = new backup_nested_element('foldermail', array('id'),
                            array('mailid', 'folderid')
                        );

            $files = new backup_nested_element('files');
            $file = new backup_nested_element('file', array('id'),
                            array('component', 'filearea', 'filename', 'contenthash', 'itemid', 'contextid', 'userid')
                        );

            // Build the tree dependiendo de forma de ver $foldermail.
            $binerbo->add_child($folders);
            $folders->add_child($folder);
            $folder->add_child($filters);
            $filters->add_child($filter);

            $binerbo->add_child($preferences);
            $preferences->add_child($preference);

            $binerbo->add_child($subfolders);
            $subfolders->add_child($subfolder);

            $binerbo->add_child($mails);
            $mails->add_child($mail);
            $mail->add_child($sends);
            $sends->add_child($send);

            $mail->add_child($foldermails);
            $foldermails->add_child($foldermail);

            $binerbo->add_child($files);
            $files->add_child($file);

            $params = array(backup::VAR_COURSEID);

            // Por la información de los correos activos del curso.
            $sql  = ' select distinct {email_folder}.* from {email_folder}  ';
            $sql .= ' left join {email_foldermail} on ( {email_foldermail}.folderid = {email_folder}.id)  ';
            $sql .= ' left join {email_mail} on ( {email_foldermail}.mailid = {email_mail}.id ) ';
            $sql .= ' where  {email_mail}.course = ? order by {email_folder}.id asc; ';

            $folder->set_source_sql($sql, $params);

            $params = array(backup::VAR_COURSEID);
            $sql  = ' select distinct {email_subfolder}.* from {email_subfolder}   ';
            $sql .= ' inner join {email_folder}  on ( {email_subfolder}.folderparentid = {email_folder}.id ' .
                ' or {email_subfolder}.folderchildid = {email_folder}.id)   ';
            $sql .= ' inner join {user_enrolments} on ( {user_enrolments}.userid  = {email_folder}.userid ) ';
            $sql .= ' inner join {enrol} on ( {enrol}.id = {user_enrolments}.enrolid) ';
            $sql .= ' where {enrol}.courseid = ? ';
            $sql .= ' order by {email_subfolder}.id asc ; ';

            $subfolder->set_source_sql($sql, $params);

            $params = array('courseid' => backup::VAR_COURSEID, 'folderid' => backup::VAR_PARENTID);
            $sql  = 'select distinct {email_filter}.* from {email_filter} ';
            $sql .= ' inner join {email_folder} on ( {email_folder}.id = {email_filter}.folderid)  ';
            $sql .= ' inner join {user_enrolments} on ( {user_enrolments}.userid  = {email_folder}.userid ) ';
            $sql .= ' inner join {enrol} on ( {enrol}.id = {user_enrolments}.enrolid) ';
            $sql .= ' where {enrol}.courseid = :courseid and  {email_filter}.folderid = :folderid';
            $sql .= ' order by {email_filter}.id asc ; ';

            $filter->set_source_sql($sql, $params);

            $params = array(backup::VAR_COURSEID);
            $sql  = ' SELECT distinct {email_preference}.* from {email_preference} ';
            $sql .= ' inner join {user_enrolments} on ( {user_enrolments}.userid  = {email_preference}.userid ) ';
            $sql .= ' inner join {enrol} on ( {enrol}.id = {user_enrolments}.enrolid) ';
            $sql .= ' where {enrol}.courseid = ? ; ';

            $preference->set_source_sql($sql, $params);

            $params = array(backup::VAR_COURSEID);

            // Forma de atacar SOLO los correos no borrados !!! en función de que estén accesibles desde una carpeta.
            $sql  = ' select distinct  ';
            $sql .= ' {files}.id, {files}.component, {files}.filearea, {files}.filename, ' .
                '{files}.contenthash, {files}.itemid, {files}.contextid, {files}.userid';
            $sql .= ' from {files}';
            $sql .= ' inner join {email_send} on ( {files}.itemid = {email_send}.mailid)  ';
            $sql .= ' inner join {email_foldermail} on ({email_foldermail}.mailid = {email_send}.mailid) ';
            $sql .= ' inner join {email_mail} on ({email_foldermail}.mailid = {email_mail}.id and {email_mail}.course = ?) ';
            $sql .= ' where trim({files}.filename)<>"." and trim({files}.filename)<>"/" ';
            $sql .= ' and {files}.component="blocks_binerbo"  ';
            $sql .= ' and {files}.filearea="attachment"; ';

            $file->set_source_sql($sql, $params);

            // Forma de atacar SOLO los correos no borrados !!! en función de que estén accesibles desde una carpeta.
            $params = array(backup::VAR_COURSEID);
            $sql  = ' select distinct  ';
            $sql .= ' {email_mail}.id, {email_mail}.userid, {email_mail}.course, {email_mail}.subject, ' .
                ' {email_mail}.timecreated, {email_mail}.body ';
            $sql .= ' from {email_mail} ';
            $sql .= ' inner join  {email_foldermail} on ({email_foldermail}.mailid ={email_mail}.id) ';
            $sql .= ' where  {email_mail}.course = ? ';

            $mail->set_source_sql($sql, $params);

            // Forma de atacar SOLO los correos no borrados !!! en función de que estén accesibles desde una carpeta.
            $params = array('courseid' => backup::VAR_COURSEID, 'mailid' => backup::VAR_PARENTID);
            $sql = ' select distinct';
            $sql .= ' {email_send}.id, {email_send}.userid, {email_send}.course, {email_send}.mailid,' .
                ' {email_send}.type, {email_send}.readed, {email_send}.sended, {email_send}.answered ';
            $sql .= ' from  {email_send} ';
            $sql .= ' inner join {email_foldermail} on ({email_foldermail}.mailid = {email_send}.mailid)  ';
            $sql .= ' inner join {email_mail} on ({email_foldermail}.mailid = {email_mail}.id '.
                'and {email_mail}.course = :courseid and {email_mail}.id = :mailid ) ; ';

            $send->set_source_sql($sql, $params);

            // OPCION 1  Cada correo decimos en que carpetas se encuentra.
            $foldermail->set_source_table('email_foldermail', array('mailid' => backup::VAR_PARENTID) , 'id ASC');

            // Define id annotations.
            $folder->annotate_ids('user', 'userid');

            $mail->annotate_ids('user', 'userid');

            $send->annotate_ids('user', 'userid');
            $send->annotate_ids('email_mail', 'mailid');

            $subfolder->annotate_ids('email_folder', 'folderparentid');
            $subfolder->annotate_ids('email_folder', 'folderchildid');

            $foldermail->annotate_ids('email_mail', 'mailid');
            $foldermail->annotate_ids('email_folder', 'folderid');

            $filter->annotate_ids('email_folder', 'folderid');

            $preference->annotate_ids('user', 'userid');

            $file->annotate_ids('email_mail', 'itemid');
            $file->annotate_ids('user', 'userid');

            // Forma de atacar SOLO los correos no borrados !!! en función de que estén accesibles desde una carpeta.
            $sql  = ' select distinct  ';
            $sql .= ' {files}.id, {files}.component, {files}.filearea, {files}.filename,'.
                ' {files}.contenthash, {files}.itemid, {files}.contextid, {files}.userid';
            $sql .= ' from {files}';
            $sql .= ' inner join {email_send} on ( {files}.itemid = {email_send}.mailid)  ';
            $sql .= ' inner join {email_foldermail} on ({email_foldermail}.mailid = {email_send}.mailid) ';
            $sql .= ' inner join {email_mail} on ({email_foldermail}.mailid = {email_mail}.id and {email_mail}.course = :curso ) ';
            $sql .= ' where trim({files}.filename)<>"." and trim({files}.filename)<>"/" ';
            $sql .= ' and {files}.component="blocks_binerbo"  ';
            $sql .= ' and {files}.filearea="attachment"; ';

            // El id del curso del que estamos haciendo el backup lo tenemos en $this->get_courseid().
            $params = array('curso' => $this->get_courseid());
            $registros = $DB->get_recordset_sql($sql, $params);

            foreach ($registros as $registro) {
                backup_structure_dbops::insert_backup_ids_record($this->get_backupid(), 'file', $registro->id);
            }
        }

        // Return the root element (email_mail), wrapped into standard block structure.
        return $this->prepare_block_structure($binerbo);
    }
}