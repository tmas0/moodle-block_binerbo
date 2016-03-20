<?php
// adrian.castillo CAM-1741
/**
 * Indicates API features that the forum supports.
 *
 *********  NO HE ENCONTRADO DONDE APARECE EL SITIO EN EL QUE PONER EL FEATURE_BACKUP_MOODLE2
 *
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function binerbo_supports($feature) {
    switch($feature) {
//        case FEATURE_GROUPS:                  return true;
//        case FEATURE_GROUPINGS:               return true;
//        case FEATURE_GROUPMEMBERSONLY:        return true;
//        case FEATURE_MOD_INTRO:               return true;
//        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
//        case FEATURE_COMPLETION_HAS_RULES:    return true;
//        case FEATURE_GRADE_HAS_GRADE:         return true;
//        case FEATURE_GRADE_OUTCOMES:          return true;
//        case FEATURE_RATE:                    return true;
        case FEATURE_BACKUP_MOODLE2:          return false /* quitado "temporalmente por mestebanez y crmas true*/;
//        case FEATURE_SHOW_DESCRIPTION:        return true;
//        case FEATURE_PLAGIARISM:              return true;
        default: return null;
    }
}


?>
