<?php

/*
 * 10-2013 adrian.castillo@uma.es CAM-1741
 * Proceso de backup-restauración de bloque email_list
 */

class restore_email_list_block_structure_step extends restore_block_instance_structure_step {

    protected function define_structure() {
        $paths = array();

	// 27-09-2013 Modificado por crmas
        // Como el metodo prepare_activity_structure(...) no se define
        // en la clase restore_structure_step, no puede invocarse como $this->prepa...
        // Lo comento como un parche hasta su resolución definitiva
        // original: Return the paths wrapped into standard activity structure
        // original: return $this->prepare_activity_structure($paths);
        // Supongo que la estructura debería ser algo que empiece asi:

        /*
         * EL ORDEN DE LLAMADA DE CADA PROCESO ES EL ORDEN EN QUE VIENEN LAS LÍNEAS EN EL XML QUE ESTAMOS PROCESANDO.
         * COMO SE LEE UN LIBRO. SI NECESITAMOS CAMBIAR EL ORDEN HAY QUE HACERLO EN EL BACKUP !!!!!!
         *
         *   Ej: Primero hay que procesar las entradas del tipo XXX ya que son necesarias cuando procesemos YYY ...
         *
         *
         * En restore_path_element EL PRIMER PARÁMETRO ES UNA ETIQUETA (NO ES UNA TABLA).
         *        (PUEDE QUE TENGA EL MISMO NOMBRE QUE UNA TABLA PERO ESTO NO ES SIGNIFICATIVO, NO TIENE UN SIGNIFICADO ESPECIAL)
         *
         * Lo que si es especial es que según el nombre de esta etiqueta tendremos que poner un nembre al proceso para hacer la lectura del XML
         *
         * Ejemplo:
         *        $paths[] = new restore_path_element('folder',  ....
         *
         *        protected function process_folder($data) { ...
         *
         *
         *
         * El segundo parámetro en "restore_path_element" hace referencia a la estructura de etiquetas en el XML
         *
         * ES MUY IMPORTANTE VER QUE TODO EL PROCESO SE BASA EN EL XML QUE CREAMOS EN EL PROCESO DE BACKUP.
         *
         *
         * CUIDADO CON ESTAS DOS FUNCIONES!!!
         *
         *               $this->get_mappingid('user', $data->userid);
         *               $this->get_mapping('user', $data->userid);
         *
         *        La primera función devuelve el nuevo id sabiendo que el anterior id era ???, (este último viene en $data->...)
         *        La segunda función devuelve todo el registro !!!
         * 
         * 
         * Para que se relacionen los IDs anterior y nuevo de los registros se usa "una tabla de relaciones",
         * la cual se rellena con isntrucciones del tipo
         *
         *       $this->set_mapping('folder', $oldid, $newitemid);
         *
         * Es importante que en la llamada anterior el parámetro 'folder' sea el mismo definido con
         *         "$paths[] = new restore_path_element('folder',  ...."
         *
         * Solo se restauran los items que se alcanzan con alguna asignación del tipo
         *        $paths[] = new restore_path_element( ...
         *
         *                    Si no se ejecuta ese código -->> no se restaura esa parte.
         *
         * Esto viene al caso si no hemos pedido datos de usuario. Se ve claramente en
         * el if
         *
         *
         *
         */

        // Opción "restaurar usuarios" tomada en la pag web al comenzar lar restauración
        $restore_users = $this->get_setting_value('users');

        /**
         * $enrol_migratetomanual = $this->get_setting_value('enrol_migratetomanual');     // Restaurar como inscripciones manuales
         * 
         *   Todos los casos de restauración comprobados funcionan correctamente (excepto el que se relata a continuación):
         *   Restauración en un curso nuevo:
         *      - opción users marcada (restore_users)
         *      - opción enrol_migratetomanual desmarcada
         *     se observa que solo se crean los profesores del curso y no todos los usuarios del mismo 
         *         (esto no es lógico ya que si se han marcado los usuarios deberían aparecer en el curso destino con el mismo 
         *          método de inscripción que tenían). 
         *     Esta falta de usuarios lleva a que los correos se creen siendo posible que los usuarios implicados no estén inscritos en el curso.
         *     No produce errores de funcionamiento.
         *     Los profesores ven sus correos.
         * 
         * todo: revisar si el funcionamiento descrito anteriormente implica algunas modificaciones.
         *       HAY QUE TENER EN CUENTA SI EL MÉTODO DE INSCRIPCIÓN ESTÁ ACTIVO EN EL CENTRO MOODLE
         *       Es muy probable que si el método de inscripción no está activo sea normal este comportamiento.
         */


        //$paths[] = new restore_path_element('block', '/block', true);
        $paths[] = new restore_path_element('email_list', '/block/email_list');

        if ($restore_users) {
            $paths[] = new restore_path_element('folder', '/block/email_list/folders/folder');
            $paths[] = new restore_path_element('filter', '/block/email_list/folders/folder/filters/filter');
            $paths[] = new restore_path_element('preference', '/block/email_list/preferences/preference');
            $paths[] = new restore_path_element('subfolder', '/block/email_list/subfolders/subfolder');
            $paths[] = new restore_path_element('mail', '/block/email_list/mails/mail');
            $paths[] = new restore_path_element('send', '/block/email_list/mails/mail/sends/send');
            $paths[] = new restore_path_element('foldermail', '/block/email_list/mails/mail/foldermails/foldermail');
            $paths[] = new restore_path_element('file', '/block/email_list/files/file');
        }

        return $paths;

    }


    protected function process_email_list($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Insertamos el registro del bloque
        $newitemid = $DB->insert_record('email_list', $data);
        
        // Después de insertar el registro llamamos a
        $this->apply_block_instance($newitemid);
    }



    protected function process_folder($data) {
                                //  array('userid','course','name','timecreated','isparenttype')
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        //  DEBIDO A QUE SIEMPRE ESTÁ A CERO ESTE VALOR DEBEMOS DEJARLO TAL CUAL !!!!!!
        //  COURSE VIENE SIEMPRE A CERO !!!!!!!!!!!!
        //  todo: Cuando se arregle que se ponga el curso en cada folder, entonces hay que tenerlo en cuenta en este punto
        //  $data->course = $this->get_courseid();
        $data->course = 0;
        $data->userid = $this->get_mappingid('user', $data->userid);


        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS !!!
        $sql  = ' select min(id) itemid from {email_folder} ';
        $sql .= ' where userid = :userid ';
        //  todo: Cuando se arregle que se ponga el curso en cada folder, entonces hay que tenerlo en cuenta en este punto
        //$sql .= '   and course = :course ';  // SIEMPRE ES CERO
        $sql .= '   and name   = :name ';
        //$params = array ('userid' => $data->userid, 'course'=>$data->course, 'name'=>$data->name);
        $params = array ('userid' => $data->userid, 'name'=>$data->name);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ){
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD
            $newitemid = $DB->insert_record('email_folder', $data);
        }else{
            $newitemid = $registro->itemid;
        }

        $this->set_mapping('folder', $oldid, $newitemid);
    }

    
    protected function process_filter($data) {
                                //   array('folderid','rules')
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->folderid = $this->get_mappingid('folder', $data->folderid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS !!!
        $sql  = ' select min(id) itemid from {email_filter} ';
        $sql .= ' where folderid = :folderid ';
        $params = array ('folderid' => $data->folderid);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ){
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD
            $newitemid = $DB->insert_record('email_filter', $data);
        }else{
            $newitemid = $registro->itemid;
        }
        
        $this->set_mapping('filter', $oldid, $newitemid);
    }

    protected function process_preference($data) {
                                //  array('userid','trackbymail','marriedfolders2courses')
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->userid = $this->get_mappingid('user', $data->userid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS !!!
        $sql  = ' select min(id) itemid from {email_preference} ';
        $sql .= ' where userid = :userid ';
        $params = array ('userid' => $data->userid);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ){
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD
            $newitemid = $DB->insert_record('email_preference', $data);
        }else{
            $newitemid = $registro->itemid;
        }
        
        $this->set_mapping('preference', $oldid, $newitemid);

    }
    
    protected function process_subfolder($data) {
                                //  array('folderparentid','folderchildid')
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->folderparentid = $this->get_mappingid('folder', $data->folderparentid);
        $data->folderchildid  = $this->get_mappingid('folder', $data->folderchildid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS !!!
        $sql  = ' select min(id) itemid from {email_subfolder} ';
        $sql .= ' where folderparentid = :folderparentid ';
        $sql .= '   and folderchildid  = :folderchildid ';
        $params = array ('folderparentid' => $data->folderparentid, 'folderchildid'=>$data->folderchildid);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ){
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD
            $newitemid = $DB->insert_record('email_subfolder', $data);
        }else{
            $newitemid = $registro->itemid;
        }

        $this->set_mapping('subfolder', $oldid, $newitemid);

    }

    protected function process_mail($data) {
                                //  array('userid','course','subject','timecreated','body')
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS !!!
        $sql  = ' select min(id) itemid from {email_mail} ';
        $sql .= ' where userid = :userid ';
        $sql .= '   and course = :course ';
        $sql .= '   and subject   = :subject ';
        $sql .= '   and timecreated   = :timecreated ';  // Es poco probable que se creen dos correos en el mismo instante
                                                         // con el mismo subject !!
        $params = array ('userid' => $data->userid, 'course'=>$data->course, 'subject'=>$data->subject, 'timecreated'=>$data->timecreated);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ){
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD
            $newitemid = $DB->insert_record('email_mail', $data);
        }else{
            $newitemid = $registro->itemid;
        }
        
        $this->set_mapping('mail', $oldid, $newitemid);

    }

    protected function process_send($data) {
                                //  array('userid','course','mailid','type','readed','sended','answered')
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->userid = $this->get_mappingid('user',$data->userid);
        $data->mailid = $this->get_mappingid('mail',$data->mailid);

        if ($data->mailid <> false ){ // Mensaje que debe existir !!
            // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS !!!
            $sql  = ' select min(id) itemid from {email_send} ';
            $sql .= ' where userid  = :userid ';
            $sql .= '   and course  = :course ';
            $sql .= '   and mailid  = :mailid ';
            $params = array ('userid' => $data->userid, 'course'=>$data->course, 'mailid'=>$data->mailid);

            $registro = $DB->get_record_sql($sql, $params);

            if ( $registro->itemid == null ){
                // Creamos el registro SI Y SOLO SI no lo tenemos en la BD
                $newitemid = $DB->insert_record('email_send', $data);
            }else{
                $newitemid = $registro->itemid;
            }

            $this->set_mapping('send', $oldid, $newitemid);
        }

    }

    protected function process_foldermail($data) {
                                //  array('mailid','folderid')
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mailid   = $this->get_mappingid('mail', $data->mailid);
        $data->folderid = $this->get_mappingid('folder', $data->folderid);

        // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS !!!
        $sql  = ' select min(id) itemid from {email_foldermail} ';
        $sql .= ' where mailid = :mailid ';
        $sql .= '   and folderid  = :folderid ';
        $params = array ('mailid' => $data->mailid, 'folderid'=>$data->folderid);

        $registro = $DB->get_record_sql($sql, $params);

        if ( $registro->itemid == null ){
            // Creamos el registro SI Y SOLO SI no lo tenemos en la BD
            $newitemid = $DB->insert_record('email_foldermail', $data);
        }else{
            $newitemid = $registro->itemid;
        }

        $this->set_mapping('foldermail', $oldid, $newitemid);

//error_log( date() . " ". __FILE__." ".__LINE__ . " " . var_dump($data) ."\n", 3, "/var/log/restore.log");

    }
    

    protected function process_file($data) {
                                //  array('component', 'filename','contenthash','filearea', 'itemid', 'contextid', 'userid')
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->itemid    = $this->get_mappingid('mail',$data->itemid);
        $data->userid = $this->get_mappingid('user',$data->userid);
        $data->contextid =  context_user::instance( $data->userid )->id;

        // Pasamos todos los ficheros relacionados adjuntos del bloque email_list al nuevo formato de moodle
        $backuppath = $this->task->get_basepath().'/files/' . backup_file_manager::get_backup_content_file_location($data->contenthash);

        // The file is not found in the backup.
        if (file_exists($backuppath)) {

            // PREVENIMOS LA EXISTENCIA DE ENTRADAS DUPLICADAS !!!
            $sql  = ' select min(id) itemid from {files} ';
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

        if ( $registro->itemid == null ){
                // Creamos el registro SI Y SOLO SI no lo tenemos en la BD
                // NOS PREPARAMOS PARA COPIAR FISICAMENTE LOS FICHEROS !!!
                // Preparamos file record object
                $file_record = array(
                    'contextid' => $data->contextid,  // CONTEXTOS DE USUARIO
                    'component' => 'blocks_email_list',         // usually = table name
                    'filearea'  => 'attachment',                // usually = table name
                    'itemid'    => $data->itemid,               // usually = ID of row in table
                                                                // any path beginning and ending in /
                    'filepath'  => "/", // filepath
                    'filename'  => $data->filename,    // any filename
                    'userid'    => $data->userid);

                $fs = get_file_storage();
                // esta función se encarga de crear la entrada en files y de poner el fichero en el sitio adecuado
                $fs->create_file_from_pathname($file_record, $backuppath);

            }else{
                $newitemid = $registro->itemid;
            }
        } else {
            // Si la entrada ya existe en la BD entonces no hacemos nada
        }

    }

}
