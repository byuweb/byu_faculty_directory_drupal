<?php
/**
 * @file
 * Contains \Drupal\byu_faculty_directory\Form\BYUFacultyDirectoryForm
 */
namespace Drupal\byu_faculty_directory\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class BYUFacultyDirectoryForm extends ConfigFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'byu_faculty_directory_admin_settings';
    }

    /**
     * @inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'byu_faculty_directory_form.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('byu_faculty_directory.config');

        $form['api_key'] = array(
          '#type' => 'textfield',
          '#title' => t('API Key for OIT Data Retrieval'),
          '#default_value' => $config->get('api_key'),
        );

        $form['fetch_all_faculty'] = array(
            '#type' => 'checkbox',
            '#title' => t('Download all faculty data from the OIT API'),
            '#default_value' => $config->get('fetch_all_faculty'),
        );

        $form['create_content'] = array(
            '#type' => 'checkbox',
            '#title' => t('Create content from cached faculty data'),
            '#default_value' => $config->get('create_content'),
        );

        /*
        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Submit'),
        );
        */
        return parent::buildForm($form, $form_state);

    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state){
        if(strlen($form_state->getValue('api_key')) < 1) {
            $form_state->setErrorByName('api_key', $this->t('Please enter something in the API key field.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){
        $fetch = $form_state->getValue('fetch_all_faculty');
        $create = $form_state->getValue('create_content');
        $old_api_key = \Drupal::config('byu_faculty_directory.config')->get('api_key');
        $new_api_key = $form_state->getValue('api_key');

        if ($fetch == 1) {
            try {
                BYUFacultyDirectoryForm::getFacultyFromAPI();
                drupal_set_message(t('Successfully retrieved faculty data from OIT!'), 'status');
            } catch (\Exception $e) {
                drupal_set_message($e->getMessage(), 'error');
            }
        }
        if ($create == 1) {
            try {
                BYUFacultyDirectoryForm::createContent();
                drupal_set_message(t('Successfully created content from cached faculty data!'), 'status');
            } catch (\Exception $e) {
                drupal_set_message($e->getMessage(), 'error');
            }
        }
        if (strcmp($old_api_key, $new_api_key) !== 0) {
            \Drupal::service('config.factory')->getEditable("byu_faculty_directory.config")->set("api_key", $new_api_key)->save();
            drupal_set_message(t('API key updated!'), 'status');
        }
    }

    /**
     * Gets ALL faculty members from API (with just basic information) and stores them in a file
     * @throws \Exception upon failed connection to OIT database
     */
    private function getFacultyFromAPI(){
        $api_key = \Drupal::config('byu_faculty_directory.config')->get('api_key');

        try {
            $result = file_get_contents("https://ws.byu.edu/services/facultyProfile/faculty?applicationKey=".$api_key);
            if ($result === false) {
                throw new \Exception('getFacultyFromAPI(): 500 Response Recieved - Invalid Application Key. No changes made to cached data');
            }
        } catch (\Exception $e) {
            throw new \Exception('getFacultyFromAPI(): Destination Unreachable - No changes made to cached data. Check application key and status of ws.byu.edu.');
        }

        $data = new \SimpleXMLElement($result);
        $netids = array();
        $netid_attribute = 'username';
        $filename = drupal_get_path('module','byu_faculty_directory').'/data/all_faculty_cache.xml';

        //Get all netids
        foreach($data->Record as $record) {
            foreach($record->children('dmd', true)->IndexEntry as $indexentry){
                //$netids[] = (string)$record->attributes()->$netid_attribute;

                //Filter by ChemE (for testing, reduces download/parsing time)
                if ((string)($indexentry->attributes()->{'text'}) === 'ENG: Chemical Engineering'){
                    $netids[] = (string)$record->attributes()->$netid_attribute;
                    break;
                }

            }
        }

        $file_header = '<?xml version="1.0"?>'."\n<facultyProfiles>\n";
        file_put_contents($filename,$file_header);

        foreach($netids as $netid){
            $netid_data = file_get_contents('https://ws.byu.edu/services/facultyProfile/faculty/'.$netid.'/profile/?applicationKey='.$api_key);
            //Strip <?xml ... ?\> so we don't have duplicates
            $netid_data = preg_replace("/<\?xml.*\?>/i", "", $netid_data);
            $netid_data = $netid_data."\n";
            file_put_contents($filename,$netid_data,FILE_APPEND);
        }
        $file_footer = "\n</facultyProfiles>";
        file_put_contents($filename,$file_footer,FILE_APPEND);
    }

    /*
     * Create content (via Faculty Member content type) for each cached faculty member
     */
    private function createContent(){
        $filename = drupal_get_path('module','byu_faculty_directory').'/data/all_faculty_cache.xml';
        $data = simplexml_load_file($filename);

        foreach($data->facultyProfile as $facultyProfile){

            //Name, Research/Teaching Interests, Bio, Website, Netid
            $firstname = $facultyProfile->Record->PCI->FNAME;
            $lastname = $facultyProfile->Record->PCI->LNAME;
            $prefname = $facultyProfile->Record->PCI->PFNAME;
            $name = $facultyProfile->Record->PCI->FNAME." ".$facultyProfile->Record->PCI->LNAME;
            $research_interests = $facultyProfile->Record->PCI->RESEARCH_INTERESTS;
            $teaching_interests = $facultyProfile->Record->PCI->TEACHING_INTERESTS;
            $bio = $facultyProfile->Record->PCI->BIO;
            $website = $facultyProfile->Record->PCI->WEBSITE;
            $netid_attribute = 'username';
            $netid = (string)$facultyProfile->Record->attributes()->$netid_attribute;

            //Emeritus Status
            $status = $facultyProfile->Record->PCI->EMP_STATUS;
            if (strcmp($status, "Retired") == 0) {
                $emeritus = 'Yes';
            } else {
                $emeritus = 'No';
            }

            //Rank, Adjunct Status
            $title = $facultyProfile->Record->PCI->RANK;
            if (strcmp($status, "Adjunct") == 0) {
                $adjunct = 'Yes';
            } else {
                $adjunct = 'No';
            }

            //Email, office, phone
            $email = $facultyProfile->Record->PCI->EMAIL;
            $office = $facultyProfile->Record->PCI->ADDRESS;
            $phone = '(' . $facultyProfile->Record->PCI->OPHONE1 . ') ' . $facultyProfile->Record->PCI->OPHONE2 . '-' . $facultyProfile->Record->PCI->OPHONE3;
            //Remove Provo, UT 84062 from office
            $office = preg_replace("/\s*,\s*provo\s*,*\s*ut\s*,*\s*84602/i", "", $office);

            //Awards
            //Name, Organization (Year) - Description
            $awards = "";
            foreach ($facultyProfile->Record->AWARDHONOR as $entry) {
                $award_entry = $entry->NAME . ', ' . $entry->ORG . ' (' . $entry->DTY_END . ')';
                if ((string)$entry->DESC) {
                    $award_entry = $award_entry . ' - ' . $entry->DESC;
                }
                $awards = $awards."$award_entry\n";
            }

            //Degrees
            //Degree in Major, School, Location (Year)
            //Dissertation: ___
            //Area of Study: ___
            $education = "<ul><br>";
            foreach ($facultyProfile->Record->EDUCATION as $entry) {
                $education_entry = $entry->DEGREE_NAME . ' in ' . $entry->MAJOR . ', ' . $entry->SCHOOL . ', ' . $entry->LOCATION . ' (' . $entry->YR_COMP . ")";
                //If there is a dissertation present
                if ((string)$entry->DISSTITLE) {
                    $education_entry = $education_entry . "\nDissertation: " . $entry->DISSTITLE;
                }
                //If there is an area of study present
                if ((string)$entry->SUPPAREA) {
                    $education_entry = $education_entry . "\nArea of Study: " . $entry->SUPPAREA;
                }
                $education = $education."<li>$education_entry</li><br>";
            }
            $education = $education."</ul>";

            //Committees
            //Role, Organization, Month Year to Month Year
            $committees = "<ul><br>";
            foreach ($facultyProfile->Record->SERVICE_DEPARTMENT as $entry) {
                $committee_entry = $entry->ROLE . ', ' . $entry->ORG;
                //If there's a starting year present
                if ((string)$entry->DTY_START) {
                    $committee_entry = $committee_entry . ',';
                    //If there's a starting month present
                    if ((string)$entry->DTM_START) {
                        $committee_entry = $committee_entry . ' ' . $entry->DTM_START;
                    }
                    $committee_entry = $committee_entry . ' ' . $entry->DTY_START;
                    //If there's an ending year present
                    if ((string)$entry->DTY_END) {
                        $committee_entry = $committee_entry . ' to';
                        //If there's an ending month present
                        if ((string)$entry->DTM_END) {
                            $committee_entry = $committee_entry . ' ' . $entry->DTM_END;
                        }
                        $committee_entry = $committee_entry . ' ' . $entry->DTY_END;
                    }
                }
                $committees = $committees."<li>$committee_entry</li><br>";
            }
            $committees = $committees."</ul>";

            //Organizations (1)
            //Name, Year to Year (Scope)
            //Description
            $organizations = "<ul><br>";
            foreach ($facultyProfile->Record->MEMBER as $entry) {
                $organization_entry = $entry->NAME;
                if ((string)$entry->DTY_START) {
                    $organization_entry = $organization_entry . ', ' . $entry->DTY_START;
                    if ((string)$entry->DTY_END) {
                        $organization_entry = $organization_entry . ' to ' . $entry->DTY_END;
                    }
                }
                $organization_entry = $organization_entry . ' (' . $entry->SCOPE . ')';
                if ((string)$entry->DESC) {
                    $organization_entry = $organization_entry . "\n" . $entry->DESC;
                }
                $organizations = $organizations."<li>$organization_entry</li><br>";
            }

            //Organizations (2)
            //Role, Organization (Elected/Appointed, Compensation), Month Year to Month Year (Audience)
            //Description
            foreach ($facultyProfile->Record->SERVICE_PROFESSIONAL as $entry) {
                //If "role" is other, we need to get the actual role
                $org_role = $entry->ROLE;
                if (strcmp((string)$org_role, "Other") == 0) {
                    $org_role = $entry->ROLEOTHER;
                }
                $org_elec_app = '';
                if (strcmp((string)$org_elec_app, "No, neither") == 0) {
                    $org_elec_app = "";
                } elseif (strcmp((string)$org_elec_app, "Yes, appointed") == 0) {
                    $org_elec_app = "Appointed";
                } else {
                    $org_elec_app = "Elected";
                }
                $org_compensation = $entry->COMPENSATED;
                if (strcmp((string)$org_compensation, "Pro Bono") != 0) {
                    $org_compensation = "";
                }
                $organization_entry = $org_role . ', ' . $entry->ORG;
                if (!empty((string)$org_elec_app)) {
                    $organization_entry = $organization_entry . ' (' . $org_elec_app;
                    if (!empty((string)$org_compensation)) {
                        $organization_entry = $organization_entry . ', ' . $org_compensation;
                    }
                    $organization_entry = $organization_entry . ')';
                } elseif (!empty((string)$org_elec_app)) {
                    if (!empty((string)$org_compensation)) {
                        $organization_entry = $organization_entry . ' (' . $org_compensation . ')';
                    }
                }
                //If there's a starting year present
                if ((string)$entry->DTY_START) {
                    $organization_entry = $organization_entry . ',';
                    //If there's a starting month present
                    if ((string)$entry->DTM_START) {
                        $organization_entry = $organization_entry . ' ' . $entry->DTM_START;
                    }
                    $organization_entry = $organization_entry . ' ' . $entry->DTY_START;
                    //If there's an ending year present
                    if ((string)$entry->DTY_END) {
                        $organization_entry = $organization_entry . ' to';
                        //If there's an ending month present
                        if ((string)$entry->DTM_END) {
                            $organization_entry = $organization_entry . ' ' . $entry->DTM_END;
                        }
                        $organization_entry = $organization_entry . ' ' . $entry->DTY_END;
                    }
                }
                if ((string)$entry->AUDIENCE) {
                    $organization_entry = $organization_entry . ' (' . $entry->AUDIENCE . ')';
                }
                if ((string)$entry->DESC) {
                    $organization_entry = $organization_entry . "\n" . $entry->DESC;
                }
                $organizations = $organizations."<li>$organization_entry</li><br>";
            }
            $organizations = $organizations."</ul>";


            //Publications (all on same line)
            //Lastname, Firstname, Lastname, Firstname, ... & Lastname, Firstname. (Day Month Year). Title. Type. Secondary Title, Publisher, City/State, Country.
            //Volume (Issue), Page, doi: DOI isbn: ISBN issn: ISSN
            $publications = "<ul><br>";
            foreach ($facultyProfile->Record->INTELLCONT as $entry) {
                //Get the name of each author
                //Format: Lastname, Firstname Initial
                $pub_authors = array();
                foreach ($entry->INTELLCONT_AUTH as $author) {
                    $author_name = $author->LNAME . ", " . $author->FNAME;
                    if ((string)$author->MNAME) {
                        $author_name = $author_name . " " . $author->MNAME;
                    }
                    $pub_authors[] = $author_name;
                }
                $pub_entry = '';
                $author_count = count($pub_authors);
                foreach ($pub_authors as $index => $auth_name) {
                    if ($index != 0 && $index == $author_count - 1) {
                        $pub_entry = $pub_entry . ', & ';
                    } elseif ($index != 0) {
                        $pub_entry = $pub_entry . ', ';
                    }
                    $pub_entry = $pub_entry . $auth_name;
                }
                $pub_entry = $pub_entry . '. (';
                if ((string)$entry->DTD_PUB) {
                    $pub_entry = $pub_entry . $entry->DTD_PUB . ' ';
                }
                if ((string)$entry->DTM_PUB) {
                    $pub_entry = $pub_entry . $entry->DTM_PUB . ' ';
                }
                if ((string)$entry->DTY_PUB) {
                    $pub_entry = $pub_entry . $entry->DTY_PUB . '). ';
                } else {
                    $pub_entry = $pub_entry . 'No date available). ';
                }
                $pub_entry = $pub_entry . $entry->TITLE . '. ' . $entry->CONTYPE . '. ';
                if ((string)$entry->TITLE_SECONDARY) {
                    $pub_entry = $pub_entry . $entry->TITLE_SECONDARY . ', ';
                }
                $pub_entry = $pub_entry . $entry->PUBLISHER;
                if ((string)$entry->PUBCTYST) {
                    $pub_entry = $pub_entry . ', ' . $entry->PUBCTYST;
                }
                if ((string)$entry->PUBCNTRY) {
                    $pub_entry = $pub_entry . ', ' . $entry->PUBCNTRY;
                }
                $pub_entry = $pub_entry . '. ';
                if ((string)$entry->VOLUME) {
                    $pub_entry = $pub_entry . $entry->VOLUME . ' ';
                }
                if ((string)$entry->ISSUE) {
                    $pub_entry = $pub_entry . '(' . $entry->ISSUE . ') ';
                }
                if ((string)$entry->PAGENUM) {
                    $pub_entry = $pub_entry . ', ' . $entry->PAGENUM . '. ';
                }
                if ((string)$entry->DOI) {
                    $pub_entry = $pub_entry . 'doi:' . $entry->DOI . ' ';
                }
                if ((string)$entry->ISBN) {
                    $pub_entry = $pub_entry . 'isbn:' . $entry->ISBN . ' ';
                }
                if ((string)$entry->ISSN) {
                    $pub_entry = $pub_entry . 'issn:' . $entry->ISSN . ' ';
                }
                $publications = $publications."<li>$pub_entry</li><br>";
            }
            $publications = $publications."</ul>";

            //Courses Taught
            //Prefix Course Suffix Section - Term Year
            //e.g. ME EN 497R Section 026 - Fall 2017
            $courses = "<ul><br>";
            foreach ($facultyProfile->Record->SCHTEACH as $entry) {
                $course_entry = $entry->COURSEPRE . ' ' . $entry->COURSENUM;
                if ((string)$entry->COURSENUM_SUFFIX) {
                    $course_entry = $course_entry . $entry->COURSENUM_SUFFIX;
                }
                $course_entry = $course_entry . ' Section ' . $entry->SECTION . ' - ' . $entry->TYT_TERM . ' ' . $entry->TYY_TERM;
                $courses = $courses."<li>$course_entry</li><br>";
            }
            $courses = $courses."</ul>";



            //Missing:
            //Profile Image
            //CV
            //"custom content"
            //"custom title"
            // 'website' as links? or have that manually entered?
            //office hours manual?
            //research short?
            //students?

            //need to add fields:
            //emeritus/adjunct
            //teaching interests
            //organizations


            //See if the faculty member already exists in the database
            $uid_query = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadByProperties(['field_uid' => $netid]);

            //Found in database!
            if ($node = reset($uid_query)) {
                //Check the manual override field for each field that OIT manages
                //If override field is false, override. If true, do nothing.

                if (!$node->field_awards_override->value){
                    $node->field_awards = $awards;
                }

                if (!$node->field_biography_override->value){
                    $node->field_biography = $bio;
                }

                if (!$node->field_committees_override->value){
                    $node->field_committees = $committees;
                }

                if (!$node->field_courses_override->value){
                    $node->field_courses = $courses;
                }

                if (!$node->field_email_override->value){
                    $node->field_email = $email;
                }

                if (!$node->field_first_name_override->value){
                    $node->field_first_name = $firstname;
                }

                if (!$node->field_last_name_override->value){
                    $node->field_last_name = $lastname;
                }

                if (!$node->field_links_override->value){
                    $node->field_links = $website;
                }

                if (!$node->field_office_location_override->value){
                    $node->field_office_location = $office;
                }

                if (!$node->field_phone_number_override->value){
                    $node->field_phone_number = $phone;
                }

                if (!$node->field_publications_override->value){
                    $node->field_publications = $publications;
                }

                if (!$node->field_research_long_override->value){
                    $node->field_research_long = $research_interests;
                }

                if (!$node->field_education_override->value){
                    $node->field_education = $education;
                }

                if (!$node->field_title_override->value){
                    $node->field_title = $title;
                }
                $node->save();
            }
            else {
                $create_array = array();
                $create_array['type'] = 'byu_faculty_member';
                $create_array['langcode'] = 'en';
                $create_array['field_awards'] = $awards;
                $create_array['field_biography'] = $bio;
                $create_array['field_committees'] = $committees;
                $create_array['field_courses'] = $courses;
                $create_array['field_email'] = $email;
                $create_array['field_first_name'] = $firstname;
                $create_array['field_last_name'] = $lastname;
                $create_array['field_links'] = $website;

                $create_array['field_office_location'] = $office;
                $create_array['field_phone_number'] = $phone;
                $create_array['field_publications'] = $publications;
                $create_array['field_research_long'] = $research_interests;
                $create_array['field_education'] = $education;
                $create_array['field_title'] = $title;
                $create_array['title'] = $name;
                $create_array['field_uid'] = $netid;

                //$create_array['field_profile_image'] =
                //$create_array['field_research_short'] =
                //$create_array['field_students'] =
                //$create_array['field_office_hours'] =

                $node = \Drupal\node\Entity\Node::create($create_array);
                $node->save();
            }


        }

    }

}
