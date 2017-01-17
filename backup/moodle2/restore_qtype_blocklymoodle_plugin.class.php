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
 * @package    moodlecore
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * restore plugin class that provides the necessary information
 * needed to restore one blocklymoodle qtype plugin
 *
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qtype_blocklymoodle_plugin extends restore_qtype_plugin {

    /**
     * Returns the paths to be handled by the plugin at question level
     */
    protected function define_question_plugin_structure() {
        return array(
            new restore_path_element('blocklymoodle', $this->get_pathfor('/blocklymoodle'))
        );
    }

    /**
     * Process the qtype/blocklymoodle element
     */
    public function process_blocklymoodle($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        if (!isset($data->responsetemplate)) {
            $data->responsetemplate = '';
        }
        if (!isset($data->responsetemplateformat)) {
            $data->responsetemplateformat = FORMAT_HTML;
        }
        if (!isset($data->responserequired)) {
            $data->responserequired = 1;
        }
        if (!isset($data->attachmentsrequired)) {
            $data->attachmentsrequired = 0;
        }

        // Detect if the question is created or mapped.
        $questioncreated = $this->get_mappingid('question_created',
                $this->get_old_parentid('question')) ? true : false;

        // If the question has been created by restore, we need to create its
        // qtype_blocklymoodle too.
        if ($questioncreated) {
            $data->questionid = $this->get_new_parentid('question');
            $newitemid = $DB->insert_record('qtype_blocklymoodle_options', $data);
            $this->set_mapping('qtype_blocklymoodle', $oldid, $newitemid);
        }
    }

    /**
     * Return the contents of this qtype to be processed by the links decoder
     */
    public static function define_decode_contents() {
        return array(
            new restore_decode_content('qtype_blocklymoodle_options', 'graderinfo', 'qtype_blocklymoodle'),
        );
    }

    /**
     * When restoring old data, that does not have the blocklymoodle options information
     * in the XML, supply defaults.
     */
    protected function after_execute_question() {
        global $DB;

        $blocklymoodleswithoutoptions = $DB->get_records_sql("
                    SELECT *
                      FROM {question} q
                     WHERE q.qtype = ?
                       AND NOT EXISTS (
                        SELECT 1
                          FROM {qtype_blocklymoodle_options}
                         WHERE questionid = q.id
                     )
                ", array('blocklymoodle'));

        foreach ($blocklymoodleswithoutoptions as $q) {
            $defaultoptions = new stdClass();
            $defaultoptions->questionid = $q->id;
            $defaultoptions->responseformat = 'editor';
            $defaultoptions->responserequired = 1;
            $defaultoptions->responsefieldlines = 15;
            $defaultoptions->attachments = 0;
            $defaultoptions->attachmentsrequired = 0;
            $defaultoptions->graderinfo = '';
            $defaultoptions->graderinfoformat = FORMAT_HTML;
            $defaultoptions->responsetemplate = '';
            $defaultoptions->responsetemplateformat = FORMAT_HTML;
            $DB->insert_record('qtype_blocklymoodle_options', $defaultoptions);
        }
    }
}
