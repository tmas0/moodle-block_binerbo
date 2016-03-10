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

define('TABLE_VAR_SORT',   1);
define('TABLE_VAR_HIDE',   2);
define('TABLE_VAR_SHOW',   3);
define('TABLE_VAR_IFIRST', 4);
define('TABLE_VAR_ILAST',  5);
define('TABLE_VAR_PAGE',   6);

class email_flexible_table {

    public $uniqueid        = null;
    public $attributes      = array();
    public $headers         = array();
    public $columns         = array();
    public $columnstyle    = array();
    public $columnclass    = array();
    public $columnsuppress = array();
    public $setup           = false;
    public $sess            = null;
    public $baseurl         = null;
    public $request         = array();

    public $iscollapsible = false;
    public $issortable    = false;
    public $usepages      = false;
    public $useinitials   = false;

    public $maxsortkeys = 2;
    public $pagesize    = 30;
    public $currpage    = 0;
    public $totalrows   = 0;
    public $sortdefaultcolumn = null;
    public $sortdefaultorder  = SORT_ASC;

    // Antoni Mas. Add.
    public $input       = false;

    public function __construct($uniqueid) {
        $this->uniqueid = $uniqueid;
        $this->request  = array(
            TABLE_VAR_SORT    => 'tsort',
            TABLE_VAR_HIDE    => 'thide',
            TABLE_VAR_SHOW    => 'tshow',
            TABLE_VAR_IFIRST  => 'tifirst',
            TABLE_VAR_ILAST   => 'tilast',
            TABLE_VAR_PAGE    => 'page'
        );
    }

    public function sortable($bool, $defaultcolumn = null, $defaultorder = SORT_ASC) {
        $this->issortable = $bool;
        $this->sortdefaultcolumn = $defaultcolumn;
        $this->sortdefaultorder  = $defaultorder;
    }

    public function collapsible($bool) {
        $this->iscollapsible = $bool;
    }

    // Antoni Mas.
    public function inputs($bool) {
        $this->input = $bool;
    }

    public function pageable($bool) {
        $this->usepages = $bool;
    }

    public function initialbars($bool) {
        $this->useinitials = $bool;
    }

    public function pagesize($perpage, $total) {
        $this->pagesize  = $perpage;
        $this->totalrows = $total;
        $this->usepages = true;
    }

    public function set_control_variables($variables) {
        foreach ($variables as $what => $variable) {
            if (isset($this->request[$what]) ) {
                $this->request[$what] = $variable;
            }
        }
    }

    public function set_attribute($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }

    public function columnsuppress($column) {
        if ( isset($this->columnsuppress[$column]) ) {
            $this->columnsuppress[$column] = true;
        }
    }

    public function columnclass($column, $classname) {
        if ( isset($this->columnclass[$column])) {
            // This space needed so that classnames don't run together in the HTML.
            $this->columnclass[$column] = ' '.$classname;
        }
    }

    public function columnstyle($column, $property, $value) {
        if (isset($this->columnstyle[$column])) {
            $this->columnstyle[$column][$property] = $value;
        }
    }

    public function columnstyle_all($property, $value) {
        foreach (array_keys($this->columns) as $column) {
            $this->columnstyle[$column][$property] = $value;
        }
    }

    public function define_baseurl($url) {
        $this->reseturl = $url;
        if ( !strpos($url, '?') ) {
            $this->baseurl = $url.'?';
        } else {
            $this->baseurl = $url.'&amp;';
        }
    }

    public function define_columns($columns) {
        $this->columns = array();
        $this->columnstyle = array();
        $this->columnclass = array();
        $colnum = 0;

        foreach ($columns as $column) {
            $this->columns[$column]         = $colnum++;
            $this->columnstyle[$column]    = array();
            $this->columnclass[$column]    = '';
            $this->columnsuppress[$column] = false;
        }
    }

    public function define_headers($headers) {
        $this->headers = $headers;
    }

    public function make_styles_string(&$styles) {
        if ( empty($styles) ) {
            return '';
        }

        $string = ' style="';
        foreach ($styles as $property => $value) {
            $string .= $property.':'.$value.';';
        }
        $string .= '"';
        return $string;
    }

    public function make_attributes_string(&$attributes) {
        if ( empty($attributes) ) {
            return '';
        }

        $string = ' ';
        foreach ($attributes as $attr => $value) {
            $string .= ($attr.'="'.$value.'" ');
        }

        return $string;
    }

    public function setup() {
        global $SESSION, $CFG;

        if ( empty($this->columns) || empty($this->uniqueid) ) {
            return false;
        }

        if ( !isset($SESSION->flextable) ) {
            $SESSION->flextable = array();
        }

        if ( !isset($SESSION->flextable[$this->uniqueid]) ) {
            $SESSION->flextable[$this->uniqueid] = new stdClass;
            $SESSION->flextable[$this->uniqueid]->uniqueid = $this->uniqueid;
            $SESSION->flextable[$this->uniqueid]->collapse = array();
            $SESSION->flextable[$this->uniqueid]->sortby   = array();
            $SESSION->flextable[$this->uniqueid]->ifirst  = '';
            $SESSION->flextable[$this->uniqueid]->ilast   = '';
        }

        $this->sess = &$SESSION->flextable[$this->uniqueid];

        if ( !empty($_GET[$this->request[TABLE_VAR_SHOW]]) &&
                isset($this->columns[$_GET[$this->request[TABLE_VAR_SHOW]]]) ) {
            // Show this column.
            $this->sess->collapse[$_GET[$this->request[TABLE_VAR_SHOW]]] = false;
        } else if ( !empty($_GET[$this->request[TABLE_VAR_HIDE]]) &&
                isset($this->columns[$_GET[$this->request[TABLE_VAR_HIDE]]]) ) {
            // Hide this column.
            $this->sess->collapse[$_GET[$this->request[TABLE_VAR_HIDE]]] = true;
            if ( array_key_exists($_GET[$this->request[TABLE_VAR_HIDE]], $this->sess->sortby) ) {
                unset($this->sess->sortby[$_GET[$this->request[TABLE_VAR_HIDE]]]);
            }
        }

        // Now, update the column attributes for collapsed columns.
        foreach (array_keys($this->columns) as $column) {
            if ( !empty($this->sess->collapse[$column]) ) {
                $this->columnstyle[$column]['width'] = '10px';
            }
        }

        if ( !empty($_GET[$this->request[TABLE_VAR_SORT]]) &&
            (isset($this->columns[$_GET[$this->request[TABLE_VAR_SORT]]]) ||
                (($_GET[$this->request[TABLE_VAR_SORT]] == 'firstname' ||
                    $_GET[$this->request[TABLE_VAR_SORT]] == 'lastname')
                && isset($this->columns['fullname']))) ) {
            if ( empty($this->sess->collapse[$_GET[$this->request[TABLE_VAR_SORT]]]) ) {
                if ( array_key_exists($_GET[$this->request[TABLE_VAR_SORT]], $this->sess->sortby) ) {
                    // This key already exists somewhere. Change its sortorder and bring it to the top.
                    $sortorder = $this->sess->sortby[$_GET[$this->request[TABLE_VAR_SORT]]] == SORT_ASC ? SORT_DESC : SORT_ASC;
                    unset($this->sess->sortby[$_GET[$this->request[TABLE_VAR_SORT]]]);
                    $this->sess->sortby = array_merge(
                            array($_GET[$this->request[TABLE_VAR_SORT]] => $sortorder),
                            $this->sess->sortby
                    );
                } else {
                    // Key doesn't exist, so just add it to the beginning of the array, ascending order.
                    $this->sess->sortby = array_merge(
                        array($_GET[$this->request[TABLE_VAR_SORT]] => SORT_ASC),
                        $this->sess->sortby
                    );
                }
                // Finally, make sure that no more than $this->maxsortkeys are present into the array.
                if ( !empty($this->maxsortkeys) && ($sortkeys = count($this->sess->sortby)) > $this->maxsortkeys ) {
                    while ($sortkeys-- > $this->maxsortkeys) {
                        array_pop($this->sess->sortby);
                    }
                }
            }
        }

        // If we didn't sort just now, then use the default sort order if one is defined and the column exists.
        if ( empty($this->sess->sortby) &&
            !empty($this->sortdefaultcolumn) &&
            (isset($this->columns[$this->sortdefaultcolumn])
                || (in_array('fullname', $this->columns)
                && in_array($this->sortdefaultcolumn,
                array('firstname', 'lastname')))) ) {
            $this->sess->sortby = array(
                $this->sortdefaultcolumn => ($this->sortdefaultorder == SORT_DESC ? SORT_DESC : SORT_ASC)
            );
        }

        if ( isset($_GET[$this->request[TABLE_VAR_ILAST]]) ) {
            if ( empty($_GET[$this->request[TABLE_VAR_ILAST]]) ||
                    is_numeric(strpos(get_string('alphabet'), $_GET[$this->request[TABLE_VAR_ILAST]])) ) {
                $this->sess->ilast = $_GET[$this->request[TABLE_VAR_ILAST]];
            }
        }

        if ( isset($_GET[$this->request[TABLE_VAR_IFIRST]]) ) {
            if ( empty($_GET[$this->request[TABLE_VAR_IFIRST]]) ||
                is_numeric(strpos(get_string('alphabet'), $_GET[$this->request[TABLE_VAR_IFIRST]])) ) {
                $this->sess->ifirst = $_GET[$this->request[TABLE_VAR_IFIRST]];
            }
        }

        if ( empty($this->baseurl) ) {
            $getcopy  = $_GET;
            unset($getcopy[$this->request[TABLE_VAR_SHOW]]);
            unset($getcopy[$this->request[TABLE_VAR_HIDE]]);
            unset($getcopy[$this->request[TABLE_VAR_SORT]]);
            unset($getcopy[$this->request[TABLE_VAR_IFIRST]]);
            unset($getcopy[$this->request[TABLE_VAR_ILAST]]);
            unset($getcopy[$this->request[TABLE_VAR_PAGE]]);

            $strippedurl = strip_querystring(qualified_me());

            if ( !empty($getcopy) ) {
                $first = false;
                $querystring = '';
                foreach ($getcopy as $var => $val) {
                    if ( !$first ) {
                        $first = true;
                        $querystring .= '?'.$var.'='.$val;
                    } else {
                        $querystring .= '&amp;'.$var.'='.$val;
                    }
                }
                $this->reseturl = $strippedurl.$querystring;
                $querystring .= '&amp;';
            } else {
                $this->reseturl = $strippedurl.$querystring;
                $querystring = '?';
            }

            $this->baseurl = strip_querystring(qualified_me()) . $querystring;
        }

        // If it's "the first time" we 've been here, forget the previous initials filters.
        if (qualified_me() == $this->reseturl) {
            $this->sess->ifirst = '';
            $this->sess->ilast  = '';
        }

        $this->currpage = optional_param($this->request[TABLE_VAR_PAGE], 0);
        $this->setup = true;

        // Always introduce the "flexible" class for the table if not specified.
        // No attributes, add flexible class.
        if (empty($this->attributes)) {
            $this->attributes['class'] = 'flexible';
            // No classes, add flexible class.
        } else if ( !isset($this->attributes['class']) ) {
            $this->attributes['class'] = 'flexible';
            // No flexible class in passed classes, add flexible class.
        } else if ( !in_array('flexible', explode(' ', $this->attributes['class'])) ) {
            $this->attributes['class'] = trim('flexible ' . $this->attributes['class']);
        }
    }

    public function get_sql_sort($uniqueid = null) {
        if ($uniqueid === null) {
            // Non-static function call.
            if ( !$this->setup ) {
                return false;
            }
            $sess = &$this->sess;
        } else {
            // Static function call.
            global $SESSION;
            if ( empty($SESSION->flextable[$uniqueid]) ) {
                return '';
            }
            $sess = &$SESSION->flextable[$uniqueid];
        }

        if ( !empty($sess->sortby) ) {
            $sortstring = '';
            foreach ($sess->sortby as $column => $order) {
                if ( !empty($sortstring) ) {
                    $sortstring .= ', ';
                }
                $sortstring .= $column.($order == SORT_ASC ? ' ASC' : ' DESC');
            }
            return $sortstring;
        }
        return '';
    }

    public function get_page_start() {
        if ( !$this->usepages ) {
            return '';
        }
        return $this->currpage * $this->pagesize;
    }

    public function get_page_size() {
        if ( !$this->usepages ) {
            return '';
        }
        return $this->pagesize;
    }

    public function get_sql_where() {
        if ( !isset($this->columns['fullname']) ) {
            return '';
        }

        $like = $DB->sql_ilike();
        if ( !empty($this->sess->ifirst) && !empty($this->sess->ilast) ) {
            return 'firstname '.$like.' \''.$this->sess->ifirst.'%\' AND lastname '.$like.' \''.$this->sess->ilast.'%\'';
        } else if ( !empty($this->sess->ifirst) ) {
            return 'firstname '.$like.' \''.$this->sess->ifirst.'%\'';
        } else if ( !empty($this->sess->ilast) ) {
            return 'lastname '.$like.' \''.$this->sess->ilast.'%\'';
        }

        return '';
    }

    public function get_initial_first() {
        if ( !$this->useinitials ) {
            return null;
        }

        return $this->sess->ifirst;
    }

    public function get_initial_last() {
        if ( !$this->useinitials ) {
            return null;
        }

        return $this->sess->ilast;
    }

    public function print_html() {
        global $CFG;

        if ( !$this->setup ) {
            return false;
        }

        // Antoni Mas.
        if ( $this->input ) {
            echo '<script type="text/javascript" language="JavaScript">
                <!--
                        function select_all( field, checked ) {
                            if ( field.length == null) {
                                field.checked = checked;
                            } else {
                                for (var i = 0; i < field.length; i++) {
                                    field[i].checked = checked ;
                                }
                            }
                        }
                    -->
                </script>';
        }

        $colcount = count($this->columns);

        // Do we need to print initial bars?.

        if ( $this->useinitials && isset($this->columns['fullname']) ) {

            $strall = get_string('all');
            $alpha  = explode(',', get_string('alphabet'));

            // Bar of first initials.

            echo '<div class="initialbar firstinitial">'.get_string('firstname').' : ';
            if ( !empty($this->sess->ifirst) ) {
                echo '<a href="'.$this->baseurl . $this->request[TABLE_VAR_IFIRST].'=">' . $strall . '</a>';
            } else {
                echo '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if ($letter == $this->sess->ifirst) {
                    echo ' <strong>'.$letter.'</strong>';
                } else {
                    echo ' <a href="'.$this->baseurl.$this->request[TABLE_VAR_IFIRST].'='.$letter.'">'.$letter.'</a>';
                }
            }
            echo '</div>';

            // Bar of last initials.

            echo '<div class="initialbar lastinitial">'.get_string('lastname').' : ';
            if ( !empty($this->sess->ilast) ) {
                echo '<a href="'.$this->baseurl.$this->request[TABLE_VAR_ILAST].'=">'.$strall.'</a>';
            } else {
                echo '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if ($letter == $this->sess->ilast) {
                    echo ' <strong>'.$letter.'</strong>';
                } else {
                    echo ' <a href="'.$this->baseurl.$this->request[TABLE_VAR_ILAST].'='.$letter.'">'.$letter.'</a>';
                }
            }
            echo '</div>';

        }

        // End of initial bars code.

        // Paging bar.
        if ($this->usepages) {
            print_paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl, $this->request[TABLE_VAR_PAGE]);
        }

        if (empty($this->data)) {
            print_heading(get_string('nothingtodisplay'));
            return true;
        }

        $suppressenabled = array_sum($this->columnsuppress);
        $suppresslastrow = null;
        // Start of main data table.

        echo '<table'.$this->make_attributes_string($this->attributes).'>';

        echo '<tr>';
        foreach ($this->columns as $column => $index) {
            $iconhide = '';
            $iconsort = '';

            if ($this->iscollapsible) {
                if ( !empty($this->sess->collapse[$column]) ) {
                    // Some headers contain < br/> tags, do not include in title.
                    $iconhide = ' <a href="'.$this->baseurl.$this->request[TABLE_VAR_SHOW].
                        '='.$column.'"><img src="'.$CFG->pixpath.'/t/switch_plus.gif" title="'.
                        get_string('show').' '.strip_tags($this->headers[$index]).'" alt="'.get_string('show').'" /></a>';
                } else if ($this->headers[$index] !== null) {
                    // Some headers contain < br/> tags, do not include in title.
                    $iconhide = ' <a href="'.$this->baseurl.$this->request[TABLE_VAR_HIDE].'='.$column.
                        '"><img src="'.$CFG->pixpath.'/t/switch_minus.gif" title="'.
                        get_string('hide').' '.strip_tags($this->headers[$index]).'" alt="'.get_string('hide').'" /></a>';
                }
            }

            $primarysortcolumn = '';
            $primarysortorder  = '';
            if (reset($this->sess->sortby)) {
                $primarysortcolumn = key($this->sess->sortby);
                $primarysortorder  = current($this->sess->sortby);
            }

            switch ($column) {
                case 'fullname':
                    if ($this->issortable) {
                        $iconsortfirst = $iconsortlast = '';
                        if ($primarysortcolumn == 'firstname') {
                            $lsortorder = get_string('asc');
                            if ($primarysortorder == SORT_ASC) {
                                $iconsortfirst = ' <img src="'.$CFG->pixpath.'/t/down.gif" alt="'.get_string('asc').'" />';
                                $fsortorder = get_string('asc');
                            } else {
                                $iconsortfirst = ' <img src="'.$CFG->pixpath.'/t/up.gif" alt="'.get_string('desc').'" />';
                                $fsortorder = get_string('desc');
                            }
                        } else if ($primarysortcolumn == 'lastname') {
                            $fsortorder = get_string('asc');
                            if ($primarysortorder == SORT_ASC) {
                                $iconsortlast = ' <img src="'.$CFG->pixpath.'/t/down.gif" alt="'.get_string('asc').'" />';
                                $lsortorder = get_string('asc');
                            } else {
                                $iconsortlast = ' <img src="'.$CFG->pixpath.'/t/up.gif" alt="'.get_string('desc').'" />';
                                $lsortorder = get_string('desc');
                            }
                        } else {
                            $fsortorder = get_string('asc');
                            $lsortorder = get_string('asc');
                        }
                        $this->headers[$index] = '<a href="'.$this->baseurl.$this->request[TABLE_VAR_SORT].'=firstname">'.
                            get_string('firstname').'<span class="accesshide">'.get_string('sortby').
                            ' '.get_string('firstname').' '.$fsortorder.'</span></a> '.$iconsortfirst.' / '.
                            '<a href="'.$this->baseurl.$this->request[TABLE_VAR_SORT].'=lastname">'.
                            get_string('lastname').'<span class="accesshide">'.get_string('sortby').' '.
                            get_string('lastname').' '.$lsortorder.'</span></a> '.$iconsortlast;
                    }
                    break;

                case 'userpic':
                    // Do nothing, do not display sortable links.
                    break;

                default:
                    if ($this->issortable) {
                        if ($primarysortcolumn == $column) {
                            if ($primarysortorder == SORT_ASC) {
                                $iconsort = ' <img src="'.$CFG->pixpath.'/t/down.gif" alt="'.get_string('asc').'" />';
                                $localsortorder = get_string('asc');
                            } else {
                                $iconsort = ' <img src="'.$CFG->pixpath.'/t/up.gif" alt="'.get_string('desc').'" />';
                                $localsortorder = get_string('desc');
                            }
                        } else {
                            $localsortorder = get_string('asc');
                        }
                        $this->headers[$index] = '<a href="'.$this->baseurl.$this->request[TABLE_VAR_SORT].'='.$column.
                            '">'.$this->headers[$index].'<span class="accesshide">'.get_string('sortby').
                            ' '.$this->headers[$index].' '.$localsortorder.'</span></a>';
                    }
                    break;
            }

            if ($this->headers[$index] === null) {
                echo '<th class="header c'.$index.$this->columnclass[$column].'" scope="col">&nbsp;</th>';
            } else if ( !empty($this->sess->collapse[$column]) ) {
                echo '<th class="header c'.$index.$this->columnclass[$column].'" scope="col">'.$iconhide.'</th>';
            } else {
                // Took out nowrap for accessibility, might need replacement.
                if ( !is_array($this->columnstyle[$column]) ) {
                    $usestyles = '';
                } else {
                    $usestyles = $this->columnstyle[$column];
                }
                echo '<th class="header c'.$index.$this->columnclass[$column].'" '.
                    $this->make_styles_string($usestyles).' scope="col">'.$this->headers[$index].
                    $iconsort.'<div class="commands">'.$iconhide.'</div></th>';
            }

        }
        echo '</tr>';

        if ( !empty($this->data) ) {
            $oddeven = 1;
            $colbyindex = array_flip($this->columns);
            foreach ($this->data as $row) {
                $oddeven = $oddeven ? 0 : 1;
                echo '<tr class="r'.$oddeven.'">';

                // If we have a separator, print it.
                if ($row === null && $colcount) {
                    echo '<td colspan="'.$colcount.'"><div class="tabledivider"></div></td>';
                } else {
                    // Antoni Mas.
                    foreach ($row->data as $index => $data) {
                        if ($index >= $colcount) {
                            break;
                        }
                        $column = $colbyindex[$index];
                        echo '<td class="cell c'.$index.$this->columnclass[$column].'"'.
                            $this->make_styles_string($this->columnstyle[$column]).' '.
                            $this->make_attributes_string($row->attributes).' >';
                        if (empty($this->sess->collapse[$column])) {
                            if ($this->columnsuppress[$column] &&
                                $suppresslastrow !== null &&
                                $suppresslastrow[$index] === $data) {
                                echo '&nbsp;';
                            } else {
                                echo $data;
                            }
                        } else {
                            echo '&nbsp;';
                        }
                        echo '</td>';
                    }
                }
                echo '</tr>';
                if ($suppressenabled) {
                    $suppresslastrow = $row;
                }
            }
        }

        // Antoni Mas. select all/none.
        if ( $this->input ) {
            $all = '<a href="javascript:void(0);" onclick="select_all(document.sendmail.mail, true);">'.
                get_string('all') . '</a>';
            $none = '<a href="javascript:void(0);" onclick="select_all(document.sendmail.mail, false);">'.
                get_string('none') . '</a>';
            echo '<tr><td colspan="'.$colcount.'">'. $all .' / '. $none .'</td></tr>';
        }

        echo '</table>';

        // Paging bar.
        if ($this->usepages) {
            print_paging_bar($this->totalrows,
                $this->currpage,
                $this->pagesize,
                $this->baseurl,
                $this->request[TABLE_VAR_PAGE]
            );
        }
    }

    public function get_html($addslashes=false) {
        global $CFG;

        if ( !$this->setup ) {
            return false;
        }

        $code = '';

        // Antoni Mas.
        if ( $this->input ) {
            $code = '<script type="text/javascript" language="JavaScript">
                <!--
                        function select_all( field, checked ) {
                            if ( field.length == null) {
                                field.checked = checked;
                            } else {
                                for (var i = 0; i < field.length; i++) {
                                    field[i].checked = checked ;
                                }
                            }
                        }
                    -->
                 </script>';
        }

        $colcount = count($this->columns);

        // Do we need to print initial bars?.

        if ($this->useinitials && isset($this->columns['fullname'])) {
            $strall = get_string('all');
            $alpha  = explode(',', get_string('alphabet'));

            // Bar of first initials.

            $code .= '<div class="initialbar firstinitial">'.get_string('firstname').' : ';
            if ( !empty($this->sess->ifirst) ) {
                $code .= '<a href="'.$this->baseurl.$this->request[TABLE_VAR_IFIRST].'=">'.$strall.'</a>';
            } else {
                $code .= '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if ($letter == $this->sess->ifirst) {
                    $code .= ' <strong>'.$letter.'</strong>';
                } else {
                    $code .= ' <a href="'.$this->baseurl.$this->request[TABLE_VAR_IFIRST].'='.$letter.'">'.$letter.'</a>';
                }
            }
            $code .= '</div>';

            // Bar of last initials.

            $code .= '<div class="initialbar lastinitial">'.get_string('lastname').' : ';
            if ( !empty($this->sess->ilast) ) {
                $code .= '<a href="'.$this->baseurl.$this->request[TABLE_VAR_ILAST].'=">'.$strall.'</a>';
            } else {
                $code .= '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if ($letter == $this->sess->ilast) {
                    $code .= ' <strong>'.$letter.'</strong>';
                } else {
                    $code .= ' <a href="'.$this->baseurl.$this->request[TABLE_VAR_ILAST].'='.$letter.'">'.$letter.'</a>';
                }
            }
            $code .= '</div>';

        }

        // End of initial bars code.

        // Paging bar.
        if ($this->usepages) {
            $code .= print_paging_bar($this->totalrows,
                $this->currpage,
                $this->pagesize,
                $this->baseurl,
                $this->request[TABLE_VAR_PAGE],
                false,
                true
            );
        } else {
            $code .= '<div>';
        }

        if (empty($this->data)) {
            $code .= print_heading(get_string('nothingtodisplay'), '', 2, 'main', true);
            if ( $addslashes ) {
                return addslashes($code);
            } else {
                return $code;
            }
        }

        $suppressenabled = array_sum($this->columnsuppress);
        $suppresslastrow = null;
        // Start of main data table.

        $code .= '<table'.$this->make_attributes_string($this->attributes).'>';

        $code .= '<tr>';
        foreach ($this->columns as $column => $index) {
            $iconhide = '';
            $iconsort = '';

            if ($this->iscollapsible) {
                if ( !empty($this->sess->collapse[$column]) ) {
                    // Some headers contain < br/> tags, do not include in title.
                    $iconhide = ' <a href="'.$this->baseurl.$this->request[TABLE_VAR_SHOW].'='.
                        $column.'"><img src="'.$CFG->pixpath.'/t/switch_plus.gif" title="'.
                        get_string('show').' '.strip_tags($this->headers[$index]).
                        '" alt="'.get_string('show').'" /></a>';
                } else if ($this->headers[$index] !== null) {
                    // Some headers contain < br/> tags, do not include in title.
                    $iconhide = ' <a href="'.$this->baseurl.$this->request[TABLE_VAR_HIDE].'='.$column.
                        '"><img src="'.$CFG->pixpath.'/t/switch_minus.gif" title="'.get_string('hide').
                        ' '.strip_tags($this->headers[$index]).'" alt="'.get_string('hide').'" /></a>';
                }
            }

            $primarysortcolumn = '';
            $primarysortorder  = '';
            if (reset($this->sess->sortby)) {
                $primarysortcolumn = key($this->sess->sortby);
                $primarysortorder  = current($this->sess->sortby);
            }

            switch ($column) {
                case 'fullname':
                    if ($this->issortable) {
                        $iconsortfirst = $iconsortlast = '';
                        if ($primarysortcolumn == 'firstname') {
                            $lsortorder = get_string('asc');
                            if ($primarysortorder == SORT_ASC) {
                                $iconsortfirst = ' <img src="'.$CFG->pixpath.'/t/down.gif" alt="'.get_string('asc').'" />';
                                $fsortorder = get_string('asc');
                            } else {
                                $iconsortfirst = ' <img src="'.$CFG->pixpath.'/t/up.gif" alt="'.get_string('desc').'" />';
                                $fsortorder = get_string('desc');
                            }
                        } else if ($primarysortcolumn == 'lastname') {
                            $fsortorder = get_string('asc');
                            if ($primarysortorder == SORT_ASC) {
                                $iconsortlast = ' <img src="'.$CFG->pixpath.'/t/down.gif" alt="'.get_string('asc').'" />';
                                $lsortorder = get_string('asc');
                            } else {
                                $iconsortlast = ' <img src="'.$CFG->pixpath.'/t/up.gif" alt="'.get_string('desc').'" />';
                                $lsortorder = get_string('desc');
                            }
                        } else {
                            $fsortorder = get_string('asc');
                            $lsortorder = get_string('asc');
                        }
                        $this->headers[$index] = '<a href="'.$this->baseurl.$this->request[TABLE_VAR_SORT].'=firstname">'.
                            get_string('firstname').'<span class="accesshide">'.get_string('sortby').' '.
                            get_string('firstname').' '.$fsortorder.'</span></a> '.$iconsortfirst.' / '.
                            '<a href="'.$this->baseurl.$this->request[TABLE_VAR_SORT].'=lastname">'.get_string('lastname').
                            '<span class="accesshide">'.get_string('sortby').' '.get_string('lastname').' '.$lsortorder.
                            '</span></a> '.$iconsortlast;
                    }
                    break;

                case 'userpic':
                    // Do nothing, do not display sortable links.
                    break;

                default:
                    if ($this->issortable) {
                        if ($primarysortcolumn == $column) {
                            if ($primarysortorder == SORT_ASC) {
                                $iconsort = ' <img src="'.$CFG->pixpath.'/t/down.gif" alt="'.get_string('asc').'" />';
                                $localsortorder = get_string('asc');
                            } else {
                                $iconsort = ' <img src="'.$CFG->pixpath.'/t/up.gif" alt="'.get_string('desc').'" />';
                                $localsortorder = get_string('desc');
                            }
                        } else {
                            $localsortorder = get_string('asc');
                        }
                        $this->headers[$index] = '<a href="'.$this->baseurl.$this->request[TABLE_VAR_SORT].'='.
                            $column.'">'.$this->headers[$index].'<span class="accesshide">'.get_string('sortby').
                            ' '.$this->headers[$index].' '.$localsortorder.'</span></a>';
                    }
                    break;
            }

            if ($this->headers[$index] === null) {
                $code .= '<th class="header c'.$index.$this->columnclass[$column].'" scope="col">&nbsp;</th>';
            } else if ( !empty($this->sess->collapse[$column]) ) {
                $code .= '<th class="header c'.$index.$this->columnclass[$column].'" scope="col">'.$iconhide.'</th>';
            } else {
                // Took out nowrap for accessibility, might need replacement.
                if ( !is_array($this->columnstyle[$column]) ) {
                    $usestyles = '';
                } else {
                    $usestyles = $this->columnstyle[$column];
                }
                $code .= '<th class="header c'.$index.$this->columnclass[$column].'" '.
                    $this->make_styles_string($usestyles).' scope="col">'.$this->headers[$index].
                    $iconsort.'<div class="commands">'.$iconhide.'</div></th>';
            }

        }
        $code .= '</tr>';

        if ( !empty($this->data) ) {
            $oddeven = 1;
            $colbyindex = array_flip($this->columns);
            foreach ($this->data as $row) {
                $oddeven = $oddeven ? 0 : 1;
                $code .= '<tr class="r'.$oddeven.'">';

                // If we have a separator, print it.
                if ($row === null && $colcount) {
                    $code .= '<td colspan="'.$colcount.'"><div class="tabledivider"></div></td>';
                } else {
                    // Antoni Mas.
                    foreach ($row->data as $index => $data) {
                        if ($index >= $colcount) {
                            break;
                        }
                        $column = $colbyindex[$index];
                        $code .= '<td class="cell c'.$index.$this->columnclass[$column].'"'.
                            $this->make_styles_string($this->columnstyle[$column]).' '.
                            $this->make_attributes_string($row->attributes).' >';
                        if (empty($this->sess->collapse[$column])) {
                            if ($this->columnsuppress[$column] &&
                                $suppresslastrow !== null &&
                                $suppresslastrow[$index] === $data) {
                                $code .= '&nbsp;';
                            } else {
                                $code .= $data;
                            }
                        } else {
                            $code .= '&nbsp;';
                        }
                        $code .= '</td>';
                    }
                }
                $code .= '</tr>';
                if ($suppressenabled) {
                    $suppresslastrow = $row;
                }
            }
        }

        // Antoni Mas. select all/none.
        if ( $this->input ) {
            $all = '<a href="javascript:void(0);" onclick="select_all(document.sendmail.mail, true);">'.
                get_string('all') . '</a>';
            $none = '<a href="javascript:void(0);" onclick="select_all(document.sendmail.mail, false);">'.
                get_string('none') . '</a>';
            $code .= '<tr><td colspan="'.$colcount.'">'. $all .' / '. $none .'</td></tr>';
        }

        $code .= '</table>';

        // Paging bar.
        if ($this->usepages) {
            $code .= print_paging_bar($this->totalrows,
                $this->currpage,
                $this->pagesize,
                $this->baseurl,
                $this->request[TABLE_VAR_PAGE],
                false,
                true
            );
        } else {
            $code .= '</div>';
        }

        if ( $addslashes ) {
            return addslashes($code);
        } else {
            return $code;
        }
    }

    public function add_data($row, $attributes = null) {
        if ( !$this->setup ) {
            return false;
        }
        $data = new stdClass;
        $data->data = $row;
        $data->attributes = $attributes;
        $this->data[] = $data;
    }

    public function add_separator() {
        if ( !$this->setup ) {
            return false;
        }
        $this->data[] = null;
    }
}
