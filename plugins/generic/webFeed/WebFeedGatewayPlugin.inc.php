<?php

/**
 * @file plugins/generic/webFeed/WebFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedGatewayPlugin
 * @ingroup plugins_generic_webFeed
 *
 * @brief Gateway component of web feed plugin
 *
 */

import('lib.pkp.classes.plugins.GatewayPlugin');

class WebFeedGatewayPlugin extends GatewayPlugin {
	/** @var WebFeedPlugin Parent plugin */
	var $_parentPlugin;

	/**
	 * Constructor
	 * @param $parentPlugin WebFeedPlugin
	 */
	public function __construct($parentPlugin) {
		parent::__construct();
		$this->_parentPlugin = $parentPlugin;
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 * @return boolean
	 */
	public function getHideManagement() {
		return true;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	public function getName() {
		return 'WebFeedGatewayPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName() {
		return __('plugins.generic.webfeed.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription() {
		return __('plugins.generic.webfeed.description');
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 */
	function getPluginPath() {
		return $this->_parentPlugin->getPluginPath();
	}

	/**
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @return boolean
	 */
	public function getEnabled() {
		return $this->_parentPlugin->getEnabled();
	}

	/**
	 * Handle fetch requests for this plugin.
	 * @param $args array Arguments.
	 * @param $request PKPRequest Request object.
	 */
	function fetch($args, $request) {
		if (!$this->_parentPlugin->getEnabled()) return false;

		// Make sure the feed type is specified and valid
		$type = array_shift($args);
		$typeMap = array(
			'rss' => 'rss.tpl',
			'rss2' => 'rss2.tpl',
			'atom' => 'atom.tpl'
		);
		$mimeTypeMap = array(
			'rss' => 'application/rdf+xml',
			'rss2' => 'application/rss+xml',
			'atom' => 'application/atom+xml'
		);
		if (!isset($typeMap[$type])) return false;

		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		// Bring in orderby constants
		import('classes.submission.SubmissionDAO');

		$args = [
			'status' => STATUS_PUBLISHED,
			'contextId' => $context->getId(),
			'count' => 1000,
			'orderBy' => ORDERBY_DATE_PUBLISHED,
		];
		$recentItems = (int) $this->_parentPlugin->getSetting($context->getId(), 'recentItems');
		if ($recentItems > 0) {
			$args['count'] = $recentItems;
		}
		$templateMgr->assign('submissions', iterator_to_array(Services::get('submission')->getMany($args)));

		$versionDao = DAORegistry::getDAO('VersionDAO'); /* @var $versionDao VersionDAO */
		$version = $versionDao->getCurrentVersion();
		$templateMgr->assign('ompVersion', $version->getVersionString());

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION); // submission.copyrightStatement

		$templateMgr->display($this->getTemplateResource($typeMap[$type]), $mimeTypeMap[$type]);

		return true;
	}
}


