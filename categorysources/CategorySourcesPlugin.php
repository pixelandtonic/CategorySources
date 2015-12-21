<?php
namespace Craft;

class CategorySourcesPlugin extends BasePlugin
{
	/**
	 * @return mixed
	 */
	public function getName()
	{
		return Craft::t('Category Sources');
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return '1.1';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '1.0.0';
	}

	/**
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'Pixel & Tonic';
	}

	/**
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://pixelandtonic.com';
	}

	/**
	 * @return string
	 */
	public function getPluginUrl()
	{
		return 'https://github.com/pixelandtonic/CategorySources';
	}

	/**
	 * @return string
	 */
	public function getDocumentationUrl()
	{
		return $this->getPluginUrl().'/blob/master/README.md';
	}

	/**
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
		return 'https://raw.githubusercontent.com/pixelandtonic/CategorySources/master/releases.json';
	}

	/**
	 * @return mixed
	 */
	public function getSettingsHtml()
	{
		$groups = craft()->categories->getAllGroups();
		$selectedGroups = $this->getSettings()->categoryGroups;
		$groupOptions = array();

		foreach ($groups as $group)
		{
			$groupOptions[] = array('label' => $group->name, 'value' => $group->id);
		}

		return craft()->templates->renderMacro('_includes/forms', 'checkboxSelectField', array(
			array(
				'first' => true,
				'label' => 'Category Groups',
				'instructions' => 'Choose which category groups should be included in entry source lists.',
				'name' => 'categoryGroups',
				'options' => $groupOptions,
				'values' => $selectedGroups,
			)
		));
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function prepSettings($settings)
	{
		if ($settings['categoryGroups'] == '*')
		{
			unset($settings['categoryGroups']);
		}

		return $settings;
	}

	/**
	 * @param $sources
	 * @param $context
	 */
	public function modifyEntrySources(&$sources, $context)
	{
		$selectedGroups = $this->getSettings()->categoryGroups;

		if (!$selectedGroups)
		{
			$selectedGroups = craft()->categories->getAllGroupIds();
		}

		foreach ($selectedGroups as $groupId)
		{
			$group = craft()->categories->getGroupById($groupId);
			$sources[] = array('heading' => Craft::t($group->name));

			$criteria = craft()->elements->getCriteria(ElementType::Category);
			$criteria->groupId = $groupId;
			$categories = craft()->elements->findElements($criteria);
			$lastSourceByLevel = array();

			foreach ($categories as $category)
			{
				$l = 'l'.$category->level;

				if ($l == 'l1')
				{
					$levelSources = &$sources;
				}
				else
				{
					$parentL = 'l'.($category->level-1);

					if (!isset($lastSourceByLevel[$parentL]['nested']))
					{
						$lastSourceByLevel[$parentL]['nested'] = array();
					}

					$levelSources = &$lastSourceByLevel[$parentL]['nested'];
				}

				$levelSources['cat:'.$category->id] = array(
					'label' => $category->title,
					'criteria' => array('relatedTo' => array('targetElement' => $category->id))
				);

				$lastSourceByLevel[$l] = &$levelSources['cat:'.$category->id];
			}
		}
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'categoryGroups' => array(AttributeType::Mixed, 'default' => array()),
		);
	}
}
