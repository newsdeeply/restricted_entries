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
				/*array( //EntryPreEdit, EntryPreCreate
					'EntryPreDelete', '/publish/',
					'page' => '/publish/',
					'delegate' => 'EntryPreDelete',
					'callback' => 'entryPreDelete',
				),*/
				// nav + security
				array(
					'page' => '/backend/',
					'delegate' => 'CanAccessPage',
					'callback' => 'canAccessPage',
				),
			);
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
			if($c['driver'] == 'publish') {
				
			}
		}

		public function addCustomAuthorColumn(array $context)
		{
			
		}

		public function addCustomAuthorColumnData(array $context)
		{
			
		}


		public function addElementstoAuthorForm(array $context)
		{
			$author = $context['author'];
			$curAuthor = Symphony::Author();
			// Takes privileges to edit this
			if (!$curAuthor->isDeveloper() &&
				!$curAuthor->isManager() &&
				!$curAuthor->isPrimaryAccount()) {
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
			static::validate(static::getRolesFromPOST(), $context['errors']);
		}

		public function authorPostCreate(array $context)
		{
			static::save(static::getRolesFromPOST(), $context['author']);
		}


		public function authorPreEdit(array $context)
		{
			static::validate(static::getRolesFromPOST(), $context['errors']);
		}

		public function authorPostEdit(array $context)
		{
			static::save(static::getRolesFromPOST(), $context['author']);
		}


		public function canAccessPage(array $context)
		{
			/*
			'allowed' => &$hasAccess,
			'page_limit' => $page_limit,
			'page_url' => $page,
			*/
/*
			$curAuthor = Symphony::Author();
			if ($curAuthor->isDeveloper() ||
				$curAuthor->isPrimaryAccount()) {
				return;
			}
			$page = Administration::instance()->Page;
			$page_context = $page->getContext();
			// Content pages only
			if ($page instanceof contentPublish) {
				$section_id = SectionManager::fetchIDFromHandle($page_context['section_handle']);
				$hasAccess = static::canAccessSection($section_id);
				if (!$hasAccess) {
					// update delegate value
					$context['allowed'] = $context['allowed'] && $hasAccess;
					// Log that thing up
					$message = "Access to {$context[page_url]} has been denied for user " .
							$curAuthor->get('username');
					Symphony::Log()->pushToLog($message, E_WARNING, true, true, false);
				}
			}
*/
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
			$roles = Symphony::Configuration()->get('roles', self::EXT_HANDLE);
			return static::parseRoles($roles);
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

			$authorCurrentRoles = array_keys(static::fetch($author->get('id')));
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

		protected static function fetch($author_id)
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
			$roles = Symphony::Configuration()->get('roles', self::EXT_HANDLE);
			if (empty($roles)) {
				Symphony::Configuration()->set('roles', '', self::EXT_HANDLE);
				Symphony::Configuration()->write();
			}
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