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

class restore_binerbo_block_structure_step extends restore_block_instance_structure_step {

    protected function define_structure() {
        $paths = array();

        // Opción "restaurar usuarios" tomada en la pag web al comenzar lar restauración.
        $rusers = $this->get_setting_value('users');

        $paths[] = new restore_path_element('binerbo', '/block/binerbo');

        if ($rusers) {
            $paths[] = new restore_path_element('folder', '/block/binerbo/folders/folder');
            $paths[] = new restore_path_element('filter', '/block/binerbo/folders/folder/filters/filter');
            $paths[] = new restore_path_element('preference', '/block/binerbo/preferences/preference');
            $paths[] = new restore_path_element('subfolder', '/block/binerbo/subfolders/subfolder');
            $paths[] = new restore_path_element('mail', '/block/binerbo/mails/mail');
            $paths[] = new restore_path_element('send', '/block/binerbo/mails/mail/sends/send');
            $paths[] = new restore_path_element('foldermail', '/block/binerbo/mails/mail/foldermails/foldermail');
            $paths[] = new restore_path_element('file', '/block/binerbo/files/file');
        }

        return $paths;
    }

    protected function process_binerbo($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Insertamos el registro del bloque.
        $newitemid = $DB->insert_record('binerbo', $data);

        // Después de insertar el registro llamamos a.
        $this->apply_block_instance($newitemid);
    }

    protected function process_folder($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = 0;
        $data->userid = $this->get_mappingid('user', $data->userid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS.
        $sql  = ' select min(id) itemid from {email_folder} ';
        $sql .= ' where userid = :userid ';
        $sql .= '   and name   = :name ';
        $params = array ('userid' => $data->userid, 'name' => $data->name);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ) {
            $newitemid = $DB->insert_record('email_folder', $data);
        } else {
            $newitemid = $registro->itemid;
        }
        $this->set_mapping('folder', $oldid, $newitemid);
    }

    protected function process_filter($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->folderid = $this->get_mappingid('folder', $data->folderid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS.
        $sql  = ' select min(id) itemid from {email_filter} ';
        $sql .= ' where folderid = :folderid ';
        $params = array ('folderid' => $data->folderid);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ) {
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD.
            $newitemid = $DB->insert_record('email_filter', $data);
        } else {
            $newitemid = $registro->itemid;
        }
        $this->set_mapping('filter', $oldid, $newitemid);
    }

    protected function process_preference($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->userid = $this->get_mappingid('user', $data->userid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS.
        $sql  = ' select min(id) itemid from {email_preference} ';
        $sql .= ' where userid = :userid ';
        $params = array ('userid' => $data->userid);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ) {
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD.
            $newitemid = $DB->insert_record('email_preference', $data);
        } else {
            $newitemid = $registro->itemid;
        }
        $this->set_mapping('preference', $oldid, $newitemid);
    }

    protected function process_subfolder($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->folderparentid = $this->get_mappingid('folder', $data->folderparentid);
        $data->folderchildid  = $this->get_mappingid('folder', $data->folderchildid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS.
        $sql  = ' select min(id) itemid from {email_subfolder} ';
        $sql .= ' where folderparentid = :folderparentid ';
        $sql .= '   and folderchildid  = :folderchildid ';
        $params = array ('folderparentid' => $data->folderparentid, 'folderchildid' => $data->folderchildid);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ) {
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD.
            $newitemid = $DB->insert_record('email_subfolder', $data);
        } else {
            $newitemid = $registro->itemid;
        }
        $this->set_mapping('subfolder', $oldid, $newitemid);
    }

    protected function process_mail($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS.
        $sql  = ' select min(id) itemid from {email_mail} ';
        $sql .= ' where userid = :userid ';
        $sql .= '   and course = :course ';
        $sql .= '   and subject   = :subject ';
        $sql .= '   and timecreated   = :timecreated ';
        // Es poco probable que se creen dos correos en el mismo instante con el mismo subject.
        $params = array(
            'userid' => $data->userid,
            'course' => $data->course,
            'subject' => $data->subject,
            'timecreated' => $data->timecreated
        );

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ) {
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD.
            $newitemid = $DB->insert_record('email_mail', $data);
        } else {
            $newitemid = $registro->itemid;
        }
        $this->set_mapping('mail', $oldid, $newitemid);
    }

    protected function process_send($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->mailid = $this->get_mappingid('mail', $data->mailid);

        if ($data->mailid <> false ) { // Mensaje que debe existir.
            // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS.
            $sql  = ' select min(id) itemid from {email_send} ';
            $sql .= ' where userid  = :userid ';
            $sql .= '   and course  = :course ';
            $sql .= '   and mailid  = :mailid ';
            $params = array ('userid' => $data->userid, 'course' => $data->course, 'mailid' => $data->mailid);

            $registro = $DB->get_record_sql($sql, $params);

            if ( $registro->itemid == null ) {
                // Creamos el registro SI Y SOLO SI no lo tenemos en la BD.
                $newitemid = $DB->insert_record('email_send', $data);
            } else {
                $newitemid = $registro->itemid;
            }
            $this->set_mapping('send', $oldid, $newitemid);
        }
    }

    protected function process_foldermail($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mailid   = $this->get_mappingid('mail', $data->mailid);
        $data->folderid = $this->get_mappingid('folder', $data->folderid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS.
        $sql  = ' select min(id) itemid from {email_foldermail} ';
        $sql .= ' where mailid = :mailid ';
        $sql .= '   and folderid  = :folderid ';
        $params = array ('mailid' => $data->mailid, 'folderid' => $data->folderid);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ) {
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD.
            $newitemid = $DB->insert_record('email_foldermail', $data);
        } else {
            $newitemid = $registro->itemid;
        }
        $this->set_mapping('foldermail', $oldid, $newitemid);
    }

    protected function process_file($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->itemid = $this->get_mappingid('mail', $data->itemid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->contextid = context_user::instance($data->userid)->id;

        // Pasamos todos los ficheros relacionados adjuntos del bloque binerbo al nuevo formato de moodle.
        $backuppath = $this->task->get_basepath() . '/files/' .
            backup_file_manager::get_backup_content_file_location($data->contenthash);

        // The file is not found in the backup.
        if (file_exists($backuppath)) {
            // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS.
            $sql = ' select min(id) itemid from {files} ';
            $sql .= ' where component  = :component ';
            $sql .= '   and filearea  = :filearea ';
            $sql .= '   and itemid  = :itemid ';
            $sql .= '   and userid  = :userid ';
            $sql .= '   and contextid  = :contextid ';
            $params = array ('component' => $data->component,
                             'filearea'  => $data->filearea,
                             'itemid'    => $data->itemid,
                             'userid'    => $data->userid,
                             'contextid' => $data->contextid);

            $registro = $DB->get_record_sql($sql, $params);

            if ( $registro->itemid == null ) {
                    // Creamos el registro SI Y SOLO SI no lo tenemos en la BD.
                    // NOS PREPARAMOS PARA COPIAR FISICAMENTE LOS FICHEROS.
                    // Preparamos file record object.
                    $frecord = array(
                        'contextid' => $data->contextid,  // CONTEXTOS DE USUARIO.
                        'component' => 'blocks_binerbo',  // Usually = table name.
                        'filearea'  => 'attachment',      // Usually = table name.
                        'itemid'    => $data->itemid,     // Usually = ID of row in table.
                                                          // Any path beginning and ending in /.
                        'filepath'  => "/",               // Filepath.
                        'filename'  => $data->filename,   // Any filename.
                        'userid'    => $data->userid);

                    $fs = get_file_storage();
                    // Esta función se encarga de crear la entrada en files y de poner el fichero en el sitio adecuado.
                    $fs->create_file_from_pathname($frecord, $backuppath);

            } else {
                $newitemid = $registro->itemid;
            }
        }
    }
}