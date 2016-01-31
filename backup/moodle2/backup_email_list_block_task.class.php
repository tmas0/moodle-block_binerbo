<?php

require_once($CFG->dirroot . '/blocks/email_list/backup/moodle2/backup_email_list_stepslib.php'); // We have structure steps
require_once($CFG->dirroot . '/blocks/email_list/backup/moodle2/backup_email_list_settingslib.php'); // Because it exists (optional)


// adrian.castillo CAM-1741
class backup_email_list_block_task extends backup_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
          $this->add_step(new backup_email_list_block_structure_step('email_list_structure', 'email_list.xml'));
    }

    public function get_fileareas() {
        ; /*
        global $DB, $course_module_to_backup;
        
        ;//return array(); // HAY QUE VER COMO SE CALCULAN LAS AREAS DE FICHEROS !!!

        $sql = ' select distinct  {files}.itemid, {files}.contextid from {files} inner join {email_send} on ( {files}.itemid = {email_send}.mailid) where  {email_send}.course = '. $course_module_to_backup ;
        echo '<br>'.$sql;
        $registros =  $DB->get_recordset_sql($sql);

        $component = 'blocks_email_list';
        $filearea  = 'attachment';

        $areas = array();

        foreach ($registros as $registro) {
            echo '<br>id->'.$registro->itemid . ', contextid->'.$registro->contextid;
            $areas[$registro->itemid ] = array ($component, $filearea, $registro->itemid, $registro->contextid);
        }
var_dump($areas);
        return $areas;
        */
    }

    public function get_configdata_encoded_attributes() {
        ;//return array(); // We need to encode some attrs in configdata
    }

    static public function encode_content_links($content) {
        return $content; // No special encoding of links
    }
}

?>
