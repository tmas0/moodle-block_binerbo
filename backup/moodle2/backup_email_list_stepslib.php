<?php
/*
 * 10-2013 adrian.castillo@uma.es CAM-1741
 * Proceso de backup-restauración de bloque email_list
 */
class backup_email_list_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        // Define each element separated
        $email_list = new backup_nested_element('email_list');


        // To know if we are including userinfo
        $users = $this->get_setting_value('users');     // Con los datos de usuario

        // Get the block
        /*$block = $DB->get_record('block_instances', array('id' => $this->task->get_blockid()));
        // Extract configdata
        $config = unserialize(base64_decode($block->configdata));
        // Get array of used rss feeds ESTA PARTE LA TENOGO QUE VER EN TRAZA PARA SABER QUE HACE !!!
        if (!empty($config->rssid)) {
            $feedids = $config->rssid;
            // Get the IN corresponding query
            list($in_sql, $in_params) = $DB->get_in_or_equal($feedids);
            // Define all the in_params as sqlparams
            foreach ($in_params as $key => $value) {
                $in_params[$key] = backup_helper::is_sqlparam($value);
            }
        }*/


        // All these source definitions only happen if we are including user info
        if ($users) {
            // Define sources

            $folders = new backup_nested_element('folders');
            $folder = new backup_nested_element('folder', array('id'),
                            array('userid','name','timecreated','isparenttype')
                            //array('userid','course','name','timecreated','isparenttype')
                        );
            $subfolders = new backup_nested_element('subfolders');
            $subfolder = new backup_nested_element('subfolder', array('id'),
                            array('folderparentid','folderchildid')
                        );

            $filters = new backup_nested_element('filters');
            $filter = new backup_nested_element('filter', array('id'),
                            array('folderid','rules')
                        );

            $preferences = new backup_nested_element('preferences');
            $preference = new backup_nested_element('preference', array('id'),
                            array('userid','trackbymail','marriedfolders2courses')
                        );

            $mails = new backup_nested_element('mails');
            $mail = new backup_nested_element('mail', array('id'),
                            array('userid','subject','timecreated','body')
                            //array('userid','course','subject','timecreated','body')
                        );

            $sends = new backup_nested_element('sends');
            $send = new backup_nested_element('send', array('id'),
                            array('userid','mailid','type','readed','sended','answered')
                            //array('userid', 'mailid', 'course','type','readed','sended','answered')
                        );

            $foldermails = new backup_nested_element('foldermails');
            $foldermail = new backup_nested_element('foldermail', array('id'),
                            array('mailid','folderid')
                            //array('mailid','folderid')
                        );


            $files = new backup_nested_element('files');
            $file = new backup_nested_element('file', array('id'),
                            array('component', 'filearea', 'filename', 'contenthash', 'itemid', 'contextid', 'userid')
                        );

            // Build the tree dependiendo de forma de ver $foldermail
            /* OPCION 1  Cada correo decimos en que carpetas se encuentra  */
            $email_list->add_child($folders);
                $folders->add_child($folder);
                    $folder->add_child($filters);
                        $filters->add_child($filter);

            $email_list->add_child($preferences);
                $preferences->add_child($preference);

            $email_list->add_child($subfolders);
                $subfolders->add_child($subfolder);

            $email_list->add_child($mails);
                $mails->add_child($mail);
                    $mail->add_child($sends);
                        $sends->add_child($send);

                    $mail->add_child($foldermails);
                        $foldermails->add_child($foldermail);

            $email_list->add_child($files);
                $files->add_child($file);

            /* OPCION 2 Cada carpeta decimos que correos tiene
            $email_list->add_child($preferences);
                $preferences->add_child($preference);

            $email_list->add_child($mails);
                $mails->add_child($mail);
                    $mail->add_child($sends);
                        $sends->add_child($send);

            $email_list->add_child($folders);
                $folders->add_child($folder);
                    $folder->add_child($filters);
                        $filters->add_child($filter);

                    $folder->add_child($foldermails);
                        $foldermails->add_child($foldermail);

            $email_list->add_child($subfolders);
                $subfolders->add_child($subfolder);
            */


            // todo: Los datos grabados en email_folder no tienen el curso con valores correctos siemre está a cero
            //$folder->set_source_table('email_folder', array('course' =>backup::VAR_COURSEID), 'id ASC');

            $params = array(backup::VAR_COURSEID);
            /* por los usuarios activos del curso
            $sql  = 'select distinct {email_folder}.* from {email_folder}  ';
            $sql .= ' left join {user_enrolments} on ( {user_enrolments}.userid  = {email_folder}.userid ) ';
            $sql .= ' left join {enrol} on ( {enrol}.id = {user_enrolments}.enrolid) ';
            $sql .= ' where {enrol}.courseid = ? ';
            $sql .= ' order by {email_folder}.id asc  '; */
            
            /* Por la informaciń de los correos activos del curso */
            $sql  = ' select distinct {email_folder}.* from {email_folder}  ';
            $sql .= ' left join {email_foldermail} on ( {email_foldermail}.folderid = {email_folder}.id)  ';
            $sql .= ' left join {email_mail} on ( {email_foldermail}.mailid = {email_mail}.id ) ';
            $sql .= ' where  {email_mail}.course = ? order by {email_folder}.id asc; ';


            $folder->set_source_sql($sql, $params);
                     //  array('userid','course','name','timecreated','isparenttype')

            $params = array(backup::VAR_COURSEID);
            $sql  = ' select distinct {email_subfolder}.* from {email_subfolder}   ';
            $sql .= ' inner join {email_folder}  on ( {email_subfolder}.folderparentid = {email_folder}.id or {email_subfolder}.folderchildid = {email_folder}.id)   ';
            $sql .= ' inner join {user_enrolments} on ( {user_enrolments}.userid  = {email_folder}.userid ) ';
            $sql .= ' inner join {enrol} on ( {enrol}.id = {user_enrolments}.enrolid) ';
            $sql .= ' where {enrol}.courseid = ? ';
            $sql .= ' order by {email_subfolder}.id asc ; ';

            $subfolder->set_source_sql($sql, $params);
                      //  array('folderparentid','folderchildid')

            $params = array('courseid' => backup::VAR_COURSEID, 'folderid' => backup::VAR_PARENTID);
            $sql  = 'select distinct {email_filter}.* from {email_filter} ';
            $sql .= ' inner join {email_folder} on ( {email_folder}.id = {email_filter}.folderid)  ';
            $sql .= ' inner join {user_enrolments} on ( {user_enrolments}.userid  = {email_folder}.userid ) ';
            $sql .= ' inner join {enrol} on ( {enrol}.id = {user_enrolments}.enrolid) ';
            $sql .= ' where {enrol}.courseid = :courseid and  {email_filter}.folderid = :folderid';
            $sql .= ' order by {email_filter}.id asc ; ';

            $filter->set_source_sql($sql, $params);
                      //  array('folderid','rules')

            $params = array(backup::VAR_COURSEID);
            $sql  = ' SELECT distinct {email_preference}.* from {email_preference} ';
            $sql .= ' inner join {user_enrolments} on ( {user_enrolments}.userid  = {email_preference}.userid ) ';
            $sql .= ' inner join {enrol} on ( {enrol}.id = {user_enrolments}.enrolid) ';
            $sql .= ' where {enrol}.courseid = ? ; ';

            $preference->set_source_sql($sql, $params);
                      //  array('userid','trackbymail','marriedfolders2courses')


            $params = array(backup::VAR_COURSEID);
          /*$sql  = ' select distinct  {files}.id, {files}.component, {files}.filearea, {files}.itemid, {files}.contextid,  {files}.userid  ';
            $sql .= ' from {files} ';
            $sql .= ' inner join {email_send} on ( {files}.itemid = {email_send}.mailid)  ';
            $sql .= ' where  {files}.component=\'blocks_email_list\' ';
            $sql .= ' and {files}.filearea=\'attachment\' ';
            $sql .= ' and  {email_send}.course = ? ' ;*/

            /* Forma de atacar SOLO los correos no borrados !!! en función de que estén accesibles desde una carpeta */
            $sql  = ' select distinct  ' ;
            $sql .= ' {files}.id, {files}.component, {files}.filearea, {files}.filename, {files}.contenthash, {files}.itemid, {files}.contextid, {files}.userid' ;
            $sql .= ' from {files}' ;
            $sql .= ' inner join {email_send} on ( {files}.itemid = {email_send}.mailid)  ' ;
            $sql .= ' inner join {email_foldermail} on ({email_foldermail}.mailid = {email_send}.mailid) ' ;
            $sql .= ' inner join {email_mail} on ({email_foldermail}.mailid = {email_mail}.id and {email_mail}.course = ?) ' ;
            $sql .= ' where trim({files}.filename)<>"." and trim({files}.filename)<>"/" ' ;
            $sql .= ' and {files}.component="blocks_email_list"  ' ;
            $sql .= ' and {files}.filearea="attachment"; ' ;

            $file->set_source_sql($sql, $params);
                      //  array('component', 'filearea', 'filename', 'contenthash', 'itemid', 'contextid', 'userid')



            /* Forma de atacar SOLO los correos no borrados !!! en función de que estén accesibles desde una carpeta */
            $params = array(backup::VAR_COURSEID);
            $sql  = ' select distinct  ' ;
            $sql .= ' {email_mail}.id, {email_mail}.userid, {email_mail}.course, {email_mail}.subject, {email_mail}.timecreated, {email_mail}.body ' ;
            $sql .= ' from {email_mail} ' ;
            $sql .= ' inner join  {email_foldermail} on ({email_foldermail}.mailid ={email_mail}.id) ' ;
            $sql .= ' where  {email_mail}.course = ? ' ;

            //$mail->set_source_table('email_mail', array( 'course' =>backup::VAR_COURSEID), 'id ASC');
            $mail->set_source_sql($sql, $params);
                      //  array('userid','course','subject','timecreated','body')




            /* Forma de atacar SOLO los correos no borrados !!! en función de que estén accesibles desde una carpeta */
            $params = array('courseid' => backup::VAR_COURSEID, 'mailid' => backup::VAR_PARENTID);
            $sql  = ' select distinct' ;
            $sql .= ' {email_send}.id, {email_send}.userid, {email_send}.course, {email_send}.mailid, {email_send}.type, {email_send}.readed, {email_send}.sended, {email_send}.answered ' ;
            $sql .= ' from  {email_send} ' ;
            $sql .= ' inner join {email_foldermail} on ({email_foldermail}.mailid = {email_send}.mailid)  ' ;
            $sql .= ' inner join {email_mail} on ({email_foldermail}.mailid = {email_mail}.id and {email_mail}.course = :courseid and {email_mail}.id = :mailid ) ; ' ;

            //$send->set_source_table('email_send', array('mailid' => backup::VAR_PARENTID, 'course' =>backup::VAR_COURSEID), 'id ASC');
            $send->set_source_sql($sql, $params);
                      //  array('userid','course','mailid','type','readed','sended','answered')


            /* OPCION 1  Cada correo decimos en que carpetas se encuentra  */
            $foldermail->set_source_table('email_foldermail', array('mailid' => backup::VAR_PARENTID) , 'id ASC');




            /* OPCION 2 Cada carpeta decimos que correos tiene
            $foldermail->set_source_table('email_foldermail', array('folderid' => backup::VAR_PARENTID) , 'id ASC');
            */
                      //  array('mailid','folderid')




            // Define id annotations
            $folder->annotate_ids('user','userid');
                     //  array('userid','course','name','timecreated','isparenttype')

            $mail->annotate_ids('user', 'userid');
                     //  array('userid','course','subject','timecreated','body')

            $send->annotate_ids('user', 'userid');
            $send->annotate_ids('email_mail', 'mailid');
                      //  array('userid','course','mailid','type','readed','sended','answered')

            $subfolder->annotate_ids('email_folder','folderparentid');
            $subfolder->annotate_ids('email_folder','folderchildid');
                      //  array('folderparentid','folderchildid')

            $foldermail->annotate_ids('email_mail','mailid');
            $foldermail->annotate_ids('email_folder','folderid');
                      //  array('mailid','folderid')

            $filter->annotate_ids('email_folder','folderid');
                      //  array('folderid','rules')

            $preference->annotate_ids('user','userid');
                      //  array('userid','trackbymail','marriedfolders2courses')

            $file->annotate_ids('email_mail','itemid');
            $file->annotate_ids('user','userid');
                      //  array('component', 'filename', 'contenthash' ,'filearea', 'itemid', 'contextid', 'userid')


            // Define file annotations
            //  LO IMPORTANTE EN ESTE PROCESO ES QUE SE GUARDAN LOS FICHEROS ESPECIFICANDO
            //$file->annotate_files('blocks_email_list', 'attachment', 'itemid', 'contextid');

            // ...->annotate_files(component, filearea, intemid, contextid);
            // Se ha probado a mano y funciona !!
            /* Como al enviar los emails se guardan los ficheros con:
                    file_save_draft_area_files( $submitted_draftid ,
                                                context_user::instance($emisor_o_receptor)->id,    // CONTEXTOS DE USUARIO context_user::instance($USER->id)->id,    // CONTEXTOS DE USUARIO $context->id,
                                                'blocks_email_list',
                                                'attachment',
                                                $email->id //Al crear correo mailid es cero !!!  $USER->id $mailid ?? Según aparece en http://docs.moodle.org/dev/File_API_internals#Implementation_of_basic_operations
                                                );
            */

            /*$sql  = ' select distinct  {files}.id, {files}.component, {files}.filearea, {files}.itemid, {files}.contextid,  {files}.userid from {files} inner join {email_send} on ( {files}.itemid = {email_send}.mailid) ';
            $sql .= ' where  trim({files}.filename)<>"." and trim({files}.filename)<>"/" ';
            $sql .= ' and {files}.component=\'blocks_email_list\' ';
            $sql .= ' and {files}.filearea=\'attachment\' ';
            $sql .= ' and  {email_send}.course = :curso ' ; */

            /* Forma de atacar SOLO los correos no borrados !!! en función de que estén accesibles desde una carpeta */
            $sql  = ' select distinct  ' ;
            $sql .= ' {files}.id, {files}.component, {files}.filearea, {files}.filename, {files}.contenthash, {files}.itemid, {files}.contextid, {files}.userid' ;
            $sql .= ' from {files}' ;
            $sql .= ' inner join {email_send} on ( {files}.itemid = {email_send}.mailid)  ' ;
            $sql .= ' inner join {email_foldermail} on ({email_foldermail}.mailid = {email_send}.mailid) ' ;
            $sql .= ' inner join {email_mail} on ({email_foldermail}.mailid = {email_mail}.id and {email_mail}.course = :curso ) ' ;
            $sql .= ' where trim({files}.filename)<>"." and trim({files}.filename)<>"/" ' ;
            $sql .= ' and {files}.component="blocks_email_list"  ' ;
            $sql .= ' and {files}.filearea="attachment"; ' ;


            // El id del curso del que estamos haciendo el backup lo tenemos en $this->get_courseid() !!!!!
            $params = array('curso'=> $this->get_courseid() );
            $registros =  $DB->get_recordset_sql($sql, $params);

            foreach ($registros as $registro) {
                //echo '<br>id->'.$registro->itemid . ', contextid->'.$registro->contextid;
                //backup_structure_dbops::annotate_files($this->get_backupid(), $registro->contextid, 'blocks_email_list', 'attachment', $registro->itemid);
                backup_structure_dbops::insert_backup_ids_record($this->get_backupid(), 'file', $registro->id);
            }


             //echo '<br>FIN ---- FIN ---- FIN ---- FIN ---- FIN ---- FIN ---- FIN ---- FIN ---- FIN ---- FIN';

        }

        // Return the root element (email_mail), wrapped into standard block structure
        return $this->prepare_block_structure($email_list);

    }
}
