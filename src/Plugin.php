<?php
namespace craft\categorysources;


use Craft;
use craft\base\Element;
use craft\categorysources\models\Settings;
use craft\db\Query;
use craft\elements\Category;
use craft\elements\Entry;
use craft\events\RegisterElementSourcesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\fields\Categories;
use yii\base\Event;






class Plugin extends \craft\base\Plugin
{
	// Properties
	// =========================================================================
    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

	/**
	 * @var array Root category IDs by category IDs
	 */
	private $_rootCategoryIds = array();

	// Public Methods
	// =========================================================================

    public function init()
    {
        parent::init();
        Event::on(Entry::class, Element::EVENT_REGISTER_SOURCES, function(RegisterElementSourcesEvent $event) {

            $selectedGroups = $this->getSettings()->categoryGroups;

            if (!$selectedGroups) {
                $selectedGroups = Craft::$app->getCategories()->getAllGroupIds();
            }

            foreach ($selectedGroups as $groupId) {

                $group = Craft::$app->getCategories()->getGroupById($groupId);

                $sources[] = ['heading' => Craft::t('category-sources', $group->name)];

                $categories = Category::find()->groupId($groupId)->all();


                foreach ($categories as $category) {
                    $l = 'l'.$category->level;

                    if ($l == 'l1') {
                        $levelSources = &$sources;
                    } else {
                        $parentL = 'l'.($category->level - 1);

                        if (!isset($lastSourceByLevel[$parentL]['nested'])) {
                            $lastSourceByLevel[$parentL]['nested'] = [];
                        }

                        $levelSources = &$lastSourceByLevel[$parentL]['nested'];
                    }

                    $levelSources['cat:'.$category->id] = [
		    	'key' => 'cat:'.$category->id,
                        'label' => $category->title,
                        'criteria' => ['relatedTo' => ['targetElement' => $category->id]]
                    ];

                    $lastSourceByLevel[$l] = &$levelSources['cat:'.$category->id];
                }
            }

            $event->sources = array_merge($event->sources, $sources);
        });
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

    // Protected Methods
    // =========================================================================

    /**
     * @return mixed
     */
    protected function settingsHtml(): string
    {
        $groups = Craft::$app->getCategories()->getAllGroups();
        $selectedGroups = $this->getSettings()->categoryGroups;
        $groupOptions = array();

        foreach ($groups as $group)
        {
            $groupOptions[] = array('label' => $group->name, 'value' => $group->id);
        }

        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'checkboxSelectField', array(
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
     * @inheritdoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }
}
