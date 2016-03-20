<?php

/*
 * 10-2013 adrian.castillo@uma.es CAM-1741
 * Proceso de backup-restauración de bloque binerbo
 */

require_once($CFG->dirroot . '/blocks/binerbo/backup/moodle2/restore_binerbo_stepslib.php'); // We have structure steps


class restore_binerbo_block_task extends restore_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        global $DB;


        // IMPORTANTE !!!!!!!!!!!!!1
        // $target backup::TARGET_[ NEW_COURSE | CURRENT_ADDING | CURRENT_DELETING | EXISTING_ADDING | EXISTING_DELETING ]
        $target = $this->get_target();

        // Dependiendo de que estemos o no borrando los contenidos del curso
        if ($target == backup::TARGET_CURRENT_DELETING || $target == backup::TARGET_EXISTING_DELETING) {
            // Borramos el contenido
            $params = array ($this->get_courseid() );
            $sql = 'select id from {email_mail} where {email_mail}.course = ? ';

            if ($correos = $DB->get_records_sql($sql, $params)) {
                $lista_de_correos = '';
                foreach ($correos as $correo) {
                    if (trim($lista_de_correos) == ''){
                        $lista_de_correos = $correo->id;
                    } else {
                        $lista_de_correos .= ', '.$correo->id;
                    }
                }
                // Borramos los ficheros del curso !!! haciendo uso del recolector de basura !!!
                $sql  = ' update {files} ';
                $sql .= ' set {files}.component = "user",  {files}.filearea  = "draft" ';
                $sql .= ' where {files}.component = "blocks_binerbo" ';
                $sql .= '   and {files}.filearea  = "attachment" ';
                $sql .= '   and {files}.itemid in ('.$lista_de_correos.') ';
                $DB->execute($sql);

                // Borramos los envios y recepciones
                $sql  = ' delete from {email_send} ';
                $sql .= ' where {email_send}.mailid in ('.$lista_de_correos.') ';
                $DB->execute($sql);

                // Borramos los envios correos
                $sql  = ' delete from {email_mail} where  {email_mail}.course = ?  ';
                $DB->execute($sql, $params);
            }

        }

        // Añadimos información
        $this->add_step(new restore_binerbo_block_structure_step('binerbo_structure', 'binerbo.xml'));
    }

    public function get_fileareas() {
        return array(); // No associated fileareas
    }

    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata
    }

    static public function define_decode_contents() {
        return array();
    }

    static public function define_decode_rules() {
        return array();
    }

}

