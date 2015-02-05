<?php
namespace Craft;

class CategorySourcesPlugin extends BasePlugin
{
	function getName()
	{
		return Craft::t('Category Sources');
	}

	function getVersion()
	{
		return '1.0';
	}

	function getDeveloper()
	{
		return 'Pixel & Tonic';
	}

	function getDeveloperUrl()
	{
		return 'http://pixelandtonic.com';
	}

	protected function defineSettings()
	{
		return array(
			'categoryGroups' => array(AttributeType::Mixed, 'default' => array()),
		);
	}

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

	public function prepSettings($settings)
	{
		if ($settings['categoryGroups'] == '*')
		{
			unset($settings['categoryGroups']);
		}

		return $settings;
	}

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
}
