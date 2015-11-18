<?php
	/*
	Copyright: Deux Huit Huit 2015
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(EXTENSIONS . '/restricted_entries/extension.driver.php');
	
	/**
	 *
	 * Field class that will represent hold the data for 
	 * @author Deux Huit Huit
	 *
	 */
	class FieldRestricted_Entries extends Field
	{
		/**
		 *
		 * Name of the field table
		 * @var string
		 */
		const FIELD_TBL_NAME = 'tbl_fields_restricted_entries';

		/**
		 *
		 * Constructor for the Field object
		 * @param mixed $parent
		 */
		public function __construct()
		{
			// call the parent constructor
			parent::__construct();
			// set the name of the field
			$this->_name = extension_restricted_entries::EXT_NAME;
			// permits to make it required
			$this->_required = false;
			// permits the make it show in the table columns
			$this->_showcolumn = static::isAllowedToEdit();
			// set as not required by default
			$this->set('required', 'no');
			// set not unique by default
			$this->set('unique', 'no');
		}

		protected static function isAllowedToEdit()
		{
			$curAuthor = Symphony::Author();
			return $curAuthor->isDeveloper() ||
				$curAuthor->isManager() ||
				$curAuthor->isPrimaryAccount();
		}

		public function get($setting = null)
		{
			if ($setting == 'show_column') {
				return static::isAllowedToEdit() ? 'yes' : 'no';
			}
			return parent::get($setting);
		}

		public function isSortable()
		{
			return false;
		}

		public function canFilter()
		{
			return false;
		}

		public function canImport()
		{
			return false;
		}

		public function canPrePopulate()
		{
			return false;
		}

		public function allowDatasourceOutputGrouping()
		{
			return false;
		}

		public function requiresSQLGrouping()
		{
			return false;
		}

		public function allowDatasourceParamOutput()
		{
			return false;
		}


		/* ********** INPUT AND FIELD *********** */


		/**
		 *
		 * Validates input
		 * Called before <code>processRawFieldData</code>
		 * @param $data
		 * @param $message
		 * @param $entry_id
		 */
		public function checkPostFieldData($data, &$message, $entry_id = null)
		{
			if (is_array($data) && !isset($data['allowed_roles'])) {
				$message = __("%s: `allowed_roles` must be an array.", array($this->get('label')));
				return self::__INVALID_FIELDS__;
			}
			return self::__OK__;
		}

		/**
		 *
		 * Process data before saving into databse.
		 * Also,
		 * Fetches oEmbed data from the source
		 *
		 * @param array $data
		 * @param int $status
		 * @param boolean $simulate
		 * @param int $entry_id
		 *
		 * @return Array - data to be inserted into DB
		 */
		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
		{
			$status = self::__OK__;
			$row = array();
			// Takes privileges to edit this
			if (!static::isAllowedToEdit()) {
				if (!$entry_id) {
					// new entry, use default from author ???
				}
				else {
					// existing entry, use current value ???
				}
			}
			else {
				// let the user set any value
				$row['allowed_roles'] = $data['allowed_roles'];
			}
			return array(
				'allowed_roles' => extension_restricted_entries::serializeRoles($row['allowed_roles'])
			);
		}

		/**
		 * This function permits parsing different field settings values
		 *
		 * @param array $settings
		 *	the data array to initialize if necessary.
		 */
		public function setFromPOST(Array $settings = array()) {

			// call the default behavior
			parent::setFromPOST($settings);

			// declare a new setting array
			$new_settings = array();

			// set new settings
			$new_settings['allowed_roles'] = (!isset($settings['allowed_roles']) ? '' : $settings['allowed_roles']);

			// save it into the array
			$this->setArray($new_settings);
		}


		/**
		 *
		 * Validates the field settings before saving it into the field's table
		 */
		public function checkFields(array &$errors, $checkForDuplicates)
		{
			parent::checkFields($errors, $checkForDuplicates);
			return self::__OK__;
		}

		/**
		 *
		 * Save field settings into the field's table
		 */
		public function commit()
		{
			// Always hide the column since the value will be dynamic
			$this->set('show_column', 'no');

			// if the default implementation works...
			if(!parent::commit()) return false;

			$id = $this->get('id');

			// exit if there is no id
			if($id == false) return false;

			// declare an array contains the field's settings
			$settings = array();

			// the field id
			$settings['field_id'] = $id;

			// the 'allowed_roles' setting
			$allowed_roles = $this->get('allowed_roles');
			$allowed_roles = extension_restricted_entries::serializeRoles($allowed_roles);
			$settings['allowed_roles'] =  empty($allowed_roles) ? null : $allowed_roles;

			// return if the SQL command was successful
			return FieldManager::saveSettings($id, $settings);
		}

		/**
		 *
		 * Remove the entry data of this field from the database, when deleting an entry
		 * @param integer|array $entry_id
		 * @param array $data
		 * @return boolean
		 */
		public function entryDataCleanup($entry_id, array $data)
		{
			if (empty($entry_id) || !parent::entryDataCleanup($entry_id, $data)) {
				return false;
			}

			return true;
		}

		/**
		 *
		 * This function allows Fields to cleanup any additional things before it is removed
		 * from the section.
		 * @return boolean
		 */
		public function tearDown()
		{
			return parent::tearDown();
		}




		/* ******* DATA SOURCE ******* */

		/**
		 * Appends data into the XML tree of a Data Source
		 * @param $wrapper
		 * @param $data
		 */
		public function appendFormattedElement(&$wrapper, $data)
		{
			$allRoles = extension_restricted_entries::getRoles();
			$currentRoles = extension_restricted_entries::parseRoles($data['allowed_roles']);
			static::fillRoles($allRoles, $currentRoles);
			foreach ($currentRoles as $key => $value) {
				$xmlItem = new XMLElement('item', $value);
				$xmlItem->setAttribute('id'. $key);
				$wrapper->appendChild($xmlItem);
			}
		}

		private static function fillRoles(array $allRoles, array &$allowedRoles)
		{
			foreach ($allowedRoles as $roleHandle) {
				$allowedRoles[$roleHandle] = isset($allRoles[$roleHandle])
					? $allRoles[$roleHandle]
					: __('** Unknown Role **');
			}
		}

		private function getAllowedRoles(array $allRoles)
		{
			$allowedRoles = extension_restricted_entries::parseRoles($this->get('allowed_roles'));
			static::fillRoles($allRoles, $allowedRoles);
			return $allowedRoles;
		}

		private static function getCurrentRoles(&$data)
		{
			$currentRoles = array();
			if (is_array($data) && isset($data['allowed_roles'])) {
				$currentRoles = extension_restricted_entries::parseRoles($data['allowed_roles']);
			}
			return $currentRoles;
		}


		/* ********* UI *********** */

		/**
		 *
		 * Builds the UI for the publish page
		 * @param XMLElement $wrapper
		 * @param mixed $data
		 * @param mixed $flagWithError
		 * @param string $fieldnamePrefix
		 * @param string $fieldnamePostfix
		 */
		public function displayPublishPanel(&$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null)
		{
			if (!static::isAllowedToEdit()) {
				$wrapper->setAttribute('class', 'irrelevant');
			}
			else {
				$allRoles = extension_restricted_entries::getRoles();
				$currentRoles = static::getCurrentRoles($data);
				$allowedRoles = $this->getAllowedRoles($allRoles);

				$label = Widget::Label($this->get('label'));
				$label->appendChild(
					static::generateRolesSelect($currentRoles, 'fields'.$fieldnamePrefix.'['.$this->get('element_name').'][allowed_roles][]'.$fieldnamePostfix, $allowedRoles)
				);

				// error management
				if($flagWithError != NULL) {
					$wrapper->appendChild(Widget::Error($label, $flagWithError));
				} else {
					$wrapper->appendChild($label);
				}
			}
		}

		public function prepareTextValue($data, $entry_id = null)
		{
			$allRoles = extension_restricted_entries::getRoles();
			$currentRoles = extension_restricted_entries::parseRoles($data['allowed_roles']);
			static::fillRoles($allRoles, $currentRoles);
			return implode(', ', $currentRoles);
		}

		/**
		 *
		 * Builds the UI for the field's settings when creating/editing a section
		 * @param XMLElement $wrapper
		 * @param array $errors
		 */
		public function displaySettingsPanel(&$wrapper, $errors = null)
		{
			/* current selected roles */
			$roles = extension_restricted_entries::parseRoles($this->get('allowed_roles'));

			/* first line, label and such */
			parent::displaySettingsPanel($wrapper, $errors);

			/* new line, roles */
			$driv_wrap = new XMLElement('div', null, array(
				'class' => 'restricted_entries-roles'
			));
			$driv_title = new XMLElement('label',
				__('Allowed roles <i>Select which roles are allowed can be chosen for the values of this field</i>')
			);
			$driv_title->appendChild(
				self::generateRolesSelect($roles, 'fields['.$this->get('sortorder').'][allowed_roles][]')
			);
			$driv_wrap->appendChild($driv_title);

			/* append to wrapper */
			$wrapper->appendChild($driv_wrap);
		}

		public static function generateRolesSelect(array $currentValues, $name, $roles = null) {
			if (!is_array($roles)) {
				$roles = extension_restricted_entries::getRoles();
			}
			asort($roles, SORT_STRING);
			$options = array();
			
			foreach ($roles as $roleHandle => $role) {
				$options[] = array($roleHandle, in_array($roleHandle, $currentValues), $role);
			}
			
			return Widget::Select($name, $options, array(
				'multiple' => 'multiple'
			));
		}


		/* ********* SQL Data Definition ************* */

		/**
		 *
		 * Creates table needed for entries of individual fields
		 */
		public function createTable()
		{
			$id = $this->get('id');
			if (!$id) {
				return false;
			}
			return Symphony::Database()->query("
				CREATE TABLE `tbl_entries_data_$id` (
					`id` int(11) 		unsigned NOT null auto_increment,
					`entry_id` 			int(11) unsigned NOT null,
					`allowed_roles` 	text,
					PRIMARY KEY  (`id`),
					UNIQUE KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

		/**
		 * Creates the table needed for the settings of the field
		 */
		public static function createFieldTable()
		{
			$tbl = self::FIELD_TBL_NAME;
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `$tbl` (
					`id` 			int(11) unsigned NOT null auto_increment,
					`field_id` 		int(11) unsigned NOT null,
					`allowed_roles` text,
					PRIMARY KEY (`id`),
					UNIQUE KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

		
		/**
		 *
		 * Drops the table needed for the settings of the field
		 */
		public static function deleteFieldTable() {
			$tbl = self::FIELD_TBL_NAME;
			
			return Symphony::Database()->query("
				DROP TABLE IF EXISTS `$tbl`
			");
		}
		
	}