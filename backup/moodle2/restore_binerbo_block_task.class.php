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

require_once($CFG->dirroot . '/blocks/binerbo/backup/moodle2/restore_binerbo_stepslib.php'); // We have structure steps.


class restore_binerbo_block_task extends restore_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        global $DB;

        $target = $this->get_target();

        // Dependiendo de que estemos o no borrando los contenidos del curso.
        if ($target == backup::TARGET_CURRENT_DELETING || $target == backup::TARGET_EXISTING_DELETING) {
            // Borramos el contenido.
            $params = array ($this->get_courseid() );
            $sql = 'select id from {email_mail} where {email_mail}.course = ? ';

            if ($correos = $DB->get_records_sql($sql, $params)) {
                $elist = '';
                foreach ($correos as $correo) {
                    if (trim($elist) == '') {
                        $elist = $correo->id;
                    } else {
                        $elist .= ', '.$correo->id;
                    }
                }
                // Borramos los ficheros del curso !!! haciendo uso del recolector de basura.
                $sql  = ' update {files} ';
                $sql .= ' set {files}.component = "user",  {files}.filearea  = "draft" ';
                $sql .= ' where {files}.component = "blocks_binerbo" ';
                $sql .= '   and {files}.filearea  = "attachment" ';
                $sql .= '   and {files}.itemid in ('.$elist.') ';
                $DB->execute($sql);

                // Borramos los envios y recepciones.
                $sql  = ' delete from {email_send} ';
                $sql .= ' where {email_send}.mailid in ('.$elist.') ';
                $DB->execute($sql);

                // Borramos los envios correos.
                $sql  = ' delete from {email_mail} where  {email_mail}.course = ?  ';
                $DB->execute($sql, $params);
            }

        }

        // Añadimos información.
        $this->add_step(new restore_binerbo_block_structure_step('binerbo_structure', 'binerbo.xml'));
    }

    public function get_fileareas() {
        return array(); // No associated fileareas.
    }

    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata.
    }

    static public function define_decode_contents() {
        return array();
    }

    static public function define_decode_rules() {
        return array();
    }
}