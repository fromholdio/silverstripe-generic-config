<?php

namespace Fromholdio\GenericConfig\Model;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\TemplateGlobalProvider;

class GenericConfig extends DataObject implements TemplateGlobalProvider
{
    private static $table_name = 'Config_Generic';
    private static $singular_name = 'General configuration';
    private static $plural_name = 'General configurations';

    private static $generic_config_admin_class;

    private static $has_one = [
        'SiteConfig' => SiteConfig::class
    ];

    private static $field_labels = [
        'MainTabSet' => 'Main'
    ];


    /**
     * @return void
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function requireDefaultRecords(): void
    {
        parent::requireDefaultRecords();
        $config = DataObject::get_one(static::class);
        if (is_null($config)) {
            static::make();
            DB::alteration_message(
                'Added generic config: ' . static::short_name(),
                'created'
            );
        }
    }

    /**
     * @return static|null
     */
    public static function curr()
    {
        $config = DataObject::get_one(static::class);
        if (is_null($config)) {
            $config = static::make();
        }
        static::singleton()->extend('updateCurr', $config);
        return $config;
    }

    /**
     * @return bool
     */
    public static function has_curr(): bool
    {
        return !is_null(static::curr());
    }

    /**
     * @return static
     * @throws \SilverStripe\ORM\ValidationException
     */
    public static function make()
    {
        $config = static::create();
        $config->write();
        return $config;
    }

    public static function short_name(): string
    {
        return ClassInfo::shortName(static::class);
    }


    public function populateDefaults(): void
    {
        parent::populateDefaults();
        $siteConfig = SiteConfig::current_site_config();
        $this->setField('SiteConfigID', $siteConfig->ID);
    }

    protected function onBeforeWrite(): void
    {
        if (empty($this->getField('SiteConfigID'))) {
            $siteConfig = SiteConfig::current_site_config();
            $this->setField('SiteConfigID', $siteConfig->ID);
        }
        parent::onBeforeWrite();
    }

    public function CMSEditLink(): ?string
    {
        $link = null;
        $adminClass = static::config()->get('generic_config_admin_class');
        if (!empty($adminClass) && class_exists($adminClass)) {
            $admin = $adminClass::singleton();
            $link = $admin->Link();
            return Director::absoluteURL($link);
        }
        return $link;
    }


    /**
     * CMS Fields
     * ----------------------------------------------------
     */

    public function getCMSFields(): FieldList
    {
        $fields = FieldList::create(
            TabSet::create('Root',
                TabSet::create('MainTabSet', 'Main')
            )
        );
        $this->extend('updateCMSFields', $fields);
        return $fields;
    }


    /**
     * Permissions
     * ----------------------------------------------------
     */

    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan('canView', $member);
        if (!is_null($extended)) {
            return $extended;
        }
        // Assuming all that can edit this object can also view it
        return $this->canEdit($member);
    }

    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan('canEdit', $member);
        if (!is_null($extended)) {
            return $extended;
        }
        $key = strtoupper(static::short_name());
        $permissionCode = 'EDIT_' . $key;
        return Permission::checkMember($member, $permissionCode);
    }

    public function providePermissions(): array
    {
        if (static::class === self::class) {
            return [];
        }
        $key = strtoupper(static::short_name());
        $name = strtolower(static::singleton()->i18n_singular_name());
        return [
            'EDIT_' . $key => [
                'name' => _t(static::class . '.EDIT_PERMISSION', 'Manage ' . $name),
                'category' => _t(
                    self::class . '.PERMISSIONS_CATEGORY',
                    'General configurations'
                ),
                'help' => _t(
                    self::class . '.EDIT_PERMISSION_HELP',
                    'Ability to edit general settings and configurations.'
                ),
                'sort' => 500
            ]
        ];
    }


    /**
     * Global Template Provider
     * ----------------------------------------------------
     */

    public static function get_template_global_variables()
    {
        return [
            static::short_name() => 'curr'
        ];
    }
}
