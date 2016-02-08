<?php
	/*
	Copyright: Deux Huit Huit 2015
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");
	
	require_once(EXTENSIONS . '/restricted_entries/fields/field.restricted_entries.php');

	/**
	 *
	 * @author Deux Huit Huit
	 * https://deuxhuithuit.com/
	 *
	 */
	class extension_restricted_entries extends Extension
	{

		/**
		 * Name of the extension
		 * @var string
		 */
		const EXT_NAME = 'Restricted Entries';

		/**
		 * Handle of the extension
		 * @var string
		 */
		const EXT_HANDLE = 'restricted_entries';

		/**
		 * Name of the table
		 * @var string
		 */
		const TBL_NAME = 'tbl_authors_restricted_entries';
		
		/**
		 * Symphony utility function that permits to
		 * implement the Observer/Observable pattern.
		 * We register here delegate that will be fired by Symphony
		 */
		public function getSubscribedDelegates()
		{
			return array(
				// assets
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendAssets',
				),
				// authors index
				array(
					'page' => '/system/authors/',
					'delegate' => 'AddCustomAuthorColumn',
					'callback' => 'addCustomAuthorColumn',
				),
				array(
					'page' => '/system/authors/',
					'delegate' => 'AddCustomAuthorColumnData',
					'callback' => 'addCustomAuthorColumnData',
				),
				// authors form
				array(
					'page' => '/system/authors/',
					'delegate' => 'AddElementstoAuthorForm',
					'callback' => 'addElementstoAuthorForm',
				),
				// delete
				array(
					'page' => '/system/authors/',
					'delegate' => 'AuthorPreDelete',
					'callback' => 'authorPreDelete',
				),
				array(
					'page' => '/system/authors/',
					'delegate' => 'AuthorPostDelete',
					'callback' => 'authorPostDelete',
				),
				// create
				array(
					'page' => '/system/authors/',
					'delegate' => 'AuthorPreCreate',
					'callback' => 'authorPreCreate',
				),
				array(
					'page' => '/system/authors/',
					'delegate' => 'AuthorPostCreate',
					'callback' => 'authorPostCreate',
				),
				// edit
				array(
					'page' => '/system/authors/',
					'delegate' => 'AuthorPreEdit',
					'callback' => 'authorPreEdit',
				),
				array(
					'page' => '/system/authors/',
					'delegate' => 'AuthorPostEdit',
					'callback' => 'authorPostEdit',
				),
				// filters
				array(
					'page' => '/publish/',
					'delegate' => 'AdjustPublishFiltering',
					'callback' => 'adjustPublishFiltering',
				),
				// security
				array(
					'page' => '/backend/',
					'delegate' => 'CanAccessPage',
					'callback' => 'canAccessPage',
				),
				// preferences
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'addCustomPreferenceFieldsets'
				),
				array(
					'page'      => '/system/preferences/',
					'delegate'  => 'Save',
					'callback'  => 'savePreferences'
				),
			);
		}

		public static function isAllowedToEdit()
		{
			$curAuthor = Symphony::Author();
			return $curAuthor != null && (
				$curAuthor->isDeveloper() ||
				$curAuthor->isManager() ||
				$curAuthor->isPrimaryAccount());
		}

		/**
		 *
		 * Appends javascript/css files references into the head, if needed
		 * @param array $context
		 */
		public function appendAssets(array $context)
		{
			// store the callback array locally
			$c = Administration::instance()->getPageCallback();
			
			// publish page
			if($c['driver'] == 'systempreferences') {
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/restricted_entries/assets/preferences.restricted_entries.js', 100, false);
			}
		}

		public function addCustomAuthorColumn(array $context)
		{
			$context['columns'][] = array(
				'label' => __('Allowed Roles'),
				'sortable' => false,
				'handle' => 'allowed-roles'
			);
		}

		public function addCustomAuthorColumnData(array $context)
		{
			$author = $context['author'];
			$text = __('None');
			$inactive = true;
			if ($author->isDeveloper() ||
				$author->isPrimaryAccount()) {
				$text = '*';
			}
			else {
				$currentRoles = static::fetchRoles($author->get('id'));
				$count = count($currentRoles);
				$inactive = $count === 0;
				if (!$inactive) {
					$allRoles = static::getRoles();
					$roles = array();
					foreach ($currentRoles as $roleHandle) {
						$roles[$roleHandle] = isset($allRoles[$roleHandle])
							? $allRoles[$roleHandle]
							: __('** Unknown Role **');
					}
					$text = implode(', ', $roles);
				}
				else {
					$text = __('None');
				}
			}
			$class = null;
			if ($inactive) {
				$class = 'inactive';
			}
			$context['tableData'][] = Widget::TableData($text, $class);
		}


		public function addElementstoAuthorForm(array $context)
		{
			$author = $context['author'];
			$curAuthor = Symphony::Author();
			// Takes privileges to edit this
			if (!static::isAllowedToEdit()) {
				return;
			}
			// Even manager should not edit their own
			if (!$curAuthor->isDeveloper() &&
				!$curAuthor->isPrimaryAccount() &&
				$curAuthor->get('id') == $author->get('id')) {
				return;
			}
			/*
			'form' => &$this->Form,
			'author' => $author,
			'fields' => $_POST['fields'],
			*/

			$group = static::createAuthorFormElements($author, static::getRoles(), $context['errors']);

			$context['form']->insertChildAt($context['form']->getNumberOfChildren() - 2, $group);
		}


		public function authorPreDelete(array $context)
		{
			// Nothing to do
		}

		public function authorPostDelete(array $context)
		{
			// TODO: delete all infos about this author
		}


		public function authorPreCreate(array $context)
		{
			if (!static::isAllowedToEdit()) {
				return;
			}
			static::validate(static::getRolesFromPOST(), $context['errors']);
		}

		public function authorPostCreate(array $context)
		{
			if (!static::isAllowedToEdit()) {
				return;
			}
			$roles = static::getRolesFromPOST();
			if ($roles == null) {
				return;
			}
			static::save($roles, $context['author']);
		}


		public function authorPreEdit(array $context)
		{
			if (!static::isAllowedToEdit()) {
				return;
			}
			static::validate(static::getRolesFromPOST(), $context['errors']);
		}

		public function authorPostEdit(array $context)
		{
			if (!static::isAllowedToEdit()) {
				return;
			}
			$roles = static::getRolesFromPOST();
			if ($roles == null) {
				return;
			}
			static::save($roles, $context['author']);
		}


		public function canAccessPage(array $context)
		{
			/*
			'allowed' => &$hasAccess,
			'page_limit' => $page_limit,
			'page_url' => $page,
			*/

			$curAuthor = Symphony::Author();
			if (static::isAllowedToEdit()) {
				return;
			}
			$page = Administration::instance()->Page;
			$page_context = $page->getContext();
			// Content pages only
			if (is_array($context['section']) && intval($context['section']['id']) > 0) {
				$entry_id = intval($page_context['entry_id']);
				if ($page_context['page'] == 'edit' && $entry_id > 0) {
					
					$filteringContext = array(
						'section-id' => $context['section']['id'],
						'joins' => '',
						'where' => ''
					);

					$hasAccess = true;
					$hasFilters = $this->adjustPublishFiltering($filteringContext);
					if ($hasFilters) {
						$entry = EntryManager::fetch($entry_id, null, 1, 0, $filteringContext['where'], $filteringContext['joins'], false, false, null, false);
						$hasAccess = !empty($entry);
					}

					if (!$hasAccess) {
						// update delegate value
						$context['allowed'] = $context['allowed'] && $hasAccess;
						// Log that thing up
						$message = "Access to entry $entry_id has been denied for user " .
								$curAuthor->get('username');
						Symphony::Log()->pushToLog($message, E_WARNING, true, true, false);
					}
				}
			}
		}

		public function adjustPublishFiltering(array &$context)
		{
			if (static::isAllowedToEdit()) {
				return;
			}
			if (!isset($context['section-id'])) {
				return;
			}
			$section = SectionManager::fetch($context['section-id']);
			if (empty($section)) {
				return;
			}
			$fields = $section->fetchFields('restricted_entries');
			if (empty($fields)) {
				return;
			}

			if (!$context['where']) {
				$context['where'] = '';
			}
			if (!$context['joins']) {
				$context['joins'] = '';
			}

			$userRoles = array_values(static::fetchRoles(Symphony::Author()->get('id')));

			foreach ($fields as $fieldId => $field) {
				$field->buildDSRetrievalSQL($userRoles, $context['joins'], $context['where'], false);
			}

			return true;
		}

		/**
		 * Delegate handle that adds Custom Preference Fieldsets
		 * @param string $page
		 * @param array $context
		 */
		public function addCustomPreferenceFieldsets($context) {
			$errors = array();
			if (isset($context['errors'][self::EXT_HANDLE])) {
				$errors = $context['errors'][self::EXT_HANDLE];
			}
			// current values
			$sectionId = (string)Symphony::Configuration()->get('roles_section_id', self::EXT_HANDLE);
			$fieldId = (string)Symphony::Configuration()->get('roles_field_id', self::EXT_HANDLE);
			
			// creates the field set
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', self::EXT_NAME));

			// create a paragraph for short instructions
			$p = new XMLElement('p', __('Please select the section and field containing your roles'), array('class' => 'help'));

			// append intro paragraph
			$fieldset->appendChild($p);

			// outter wrapper
			$out_wrapper = new XMLElement('div');

			// create a wrapper
			$wrapper = new XMLElement('div');
			$wrapper->setAttribute('class', 'two columns');
			$out_wrapper->appendChild($wrapper);

			// append labels to field set
			$label = Widget::Label('Roles Section',
				Widget::Select('settings[restricted_entries][roles_section_id]', $options, array('class' => 'js-restricted-entries-section', 'data-value' => $sectionId)),
				null, null, array('class' => 'column')
			);
			if (isset($errors['roles_section_id'])) {
				$label = Widget::Error($label, $errors['roles_section_id']);
			}
			$wrapper->appendChild($label);
			
			// append labels to field set
			$label = Widget::Label('Role name Field',
				Widget::Select('settings[restricted_entries][roles_field_id]', $options, array('class' => 'js-restricted-entries-field', 'data-value' => $fieldId)),
				null, null, array('class' => 'column'));
			if (isset($errors['roles_field_id'])) {
				$label = Widget::Error($label, $errors['roles_field_id']);
			}
			$wrapper->appendChild($label);

			// wrapper into fieldset
			$fieldset->appendChild($out_wrapper);

			// adds the field set to the wrapper
			$context['wrapper']->appendChild($fieldset);
		}

		/**
		 * Delegate handle that saves the preferences
		 * Saves settings and cleans the database acconding to the new settings
		 * @param array $context
		 */
		public function savePreferences(&$context){
			$settings = $context['settings']['restricted_entries'];
			
			// validate data
			if (!is_array($settings) || empty($settings)) {
				return;
			}
			
			// sanitize data
			foreach ($settings as $key => $value) {
				$settings[$key] = General::intval($value);
			}
			
			// validate section
			if (empty($settings['roles_section_id']) || $settings['roles_section_id'] === -1) {
				$context['errors'][self::EXT_HANDLE]['roles_section_id'] = __('You must select a section for your roles');
				return;
			}
			else if (SectionManager::fetch($settings['roles_section_id']) == null) {
				$context['errors'][self::EXT_HANDLE]['roles_section_id'] = __('The selected section is invalid');
				return;
			}
			
			// validate field
			if (empty($settings['roles_field_id']) || $settings['roles_field_id'] === -1) {
				$context['errors'][self::EXT_HANDLE]['roles_field_id'] = __('You must select a field for your roles name');
				return;
			}
			$field = FieldManager::fetch($settings['roles_field_id']);
			if (!$field) {
				$context['errors'][self::EXT_HANDLE]['roles_field_id'] = __('The selected field is invalid');
				return;
			}
			else if (General::intval($field->get('parent_section')) !== $settings['roles_section_id']) {
				$context['errors'][self::EXT_HANDLE]['roles_field_id'] = __('The selected field is not in the selected section schema');
				return;
			}
			
			// save config
			foreach ($settings as $key => $value) {
				Symphony::Configuration()->get($key, $value, self::EXT_HANDLE);
			}
		}

		/* ********* LIB ******* */

		public static function parseRoles($roles)
		{
			if (empty($roles)) {
				return array();
			}
			$roles = array_filter(array_map(trim, explode(',', $roles)));
			$normalizedRoles = array();
			foreach ($roles as $role) {
				$normalizedRoles[Lang::createHandle($role)] = $role;
			}
			return $normalizedRoles;
		}

		public static function serializeRoles($roles)
		{
			if (is_array($roles)) {
				$roles = implode(',', $roles);
			}
			return $roles;
		}

		public static function getRoles()
		{
			$sectionId = Symphony::Configuration()->get('roles_section_id', self::EXT_HANDLE);
			$fieldId = Symphony::Configuration()->get('roles_field_id', self::EXT_HANDLE);
			if (!$sectionId || !$fieldId) {
				return array();
			}
			$field = FieldManager::fetch($fieldId);
			$entries = EntryManager::fetch(null, $sectionId);
			$roles = array();
			foreach ($entries as $entry) {
				$edata = $entry->getData();
				$roles[$entry->get('id')] = $field->prepareTextValue($edata[$fieldId], $entry->get('id'));
			}
			return $roles;
		}

		protected static function createAuthorFormElements(Author &$author, array $roles, $errors)
		{
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Restricted Entries')));
			$help = new XMLElement('p', __('Insert help message here'), array('class' => 'help'));
			$group->appendChild($help);

			$label = Widget::Label(__('Roles'));

			$attributes = array(
				'multiple' => 'multiple',
				'class' => 'required',
				'required' => 'required',
			);

			$authorCurrentRoles = array_keys(static::fetchRoles($author->get('id')));
			$options = array(
				array('*', in_array('*', $authorCurrentRoles), __('All roles'))
			);

			foreach ($roles as $roleHandle => $role) {
				$options[] = array($roleHandle, in_array($roleHandle, $authorCurrentRoles), $role);
			}
			$select = Widget::Select('restr_entries[allowed_roles][]', $options, $attributes);
			$label->appendChild($select);

			if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
				is_array($errors) &&
				isset($errors['restr_entries'])) {
				$group->appendChild(Widget::Error($label, $errors['restr_entries']));
			}
			else {
				$group->appendChild($label);
			}

			return $group;
		}

		protected static function getRolesFromPOST() {
			if (!isset($_POST['restr_entries']) ||
				!isset($_POST['restr_entries']['allowed_roles']) ||
				!is_array($_POST['restr_entries']['allowed_roles'])) {
				return null;
			}
			return $_POST['restr_entries']['allowed_roles'];
		}

		protected static function isValid($roles)
		{
			return $roles !== null && is_array($roles) && !empty($roles);
		}

		protected static function validate($roles, &$errors)
		{
			if (!static::isValid($roles)) {
				if (is_array($errors)) {
					$errors['restr_entries'] = 'Please select a value for Restricted Entries Roles';
				}
				return false;
			}
			return true;
		}

		protected static function save(array $roles, Author $author)
		{
			$ret = Symphony::Database()->delete(self::TBL_NAME,
				'`author_id` = ' . intval($author->get('id'))
			);
			$ret = $ret && Symphony::Database()->insert(array(
				'author_id' => intval($author->get('id')),
				'roles' => static::serializeRoles($roles),
			), self::TBL_NAME, true);
			return $ret;
		}

		public static function fetchRoles($author_id)
		{
			$roles = Symphony::Database()->fetchCol('roles', sprintf("
				SELECT `roles` FROM `%s`
					WHERE `author_id` = %d
			", self::TBL_NAME, intval($author_id)));
			return static::parseRoles(current($roles));
		}

		protected static function createTable()
		{
			$tbl = self::TBL_NAME;
			$ret = Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `$tbl` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`author_id` int(11) unsigned NOT NULL,
					`roles` text,
					PRIMARY KEY (`id`),
					UNIQUE KEY `author_id` (`author_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			");
			return $ret;
		}

		protected static function dropTable()
		{
			$tbl = self::TBL_NAME;
			$ret = Symphony::Database()->query("DROP TABLE IF EXISTS `$tbl`");
			return $ret;
		}

		/* ********* INSTALL/UPDATE/UNINSTALL ******* */

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function install()
		{
			return static::createTable() && FieldRestricted_Entries::createFieldTable();
		}

		/**
		 * This method will update the extension according to the
		 * previous and current version parameters.
		 * @param string $previousVersion
		 */
		public function update($previousVersion = false)
		{
			$ret = true;
			
			if (!$previousVersion) {
				$previousVersion = '0.0.1';
			}
			
			// less than 0.1.0
			if ($ret && version_compare($previousVersion, '0.1.0') == -1) {
				
			}
			
			return $ret;
		}

		/**
		 * Drops the table needed for the settings of the field
		 */
		public function uninstall()
		{
			return static::dropTable() && FieldRestricted_Entries::deleteFieldTable();
		}

	}