<?php

namespace Fromholdio\GenericConfig\Admin;

use LittleGiant\SingleObjectAdmin\SingleObjectAdmin;
use SGN\HasOneEdit\UpdateFormExtension;
use SilverStripe\Security\Permission;
use Fromholdio\GenericConfig\Model\GenericConfig;

abstract class GenericConfigAdmin extends SingleObjectAdmin
{
    private static $menu_title = 'Config';
    private static $tree_class = GenericConfig::class;

    private static $menu_icon = null;
    private static $menu_icon_class = 'font-icon-cog';

    private static $allowed_actions = [
        'EditForm',
        'ItemEditForm'
    ];

    private static $extensions = [
        UpdateFormExtension::class
    ];

    public function ItemEditForm()
    {
        return $this->EditForm();
    }

    public function canView($member = null)
    {
        $treeClass = static::config()->get('tree_class');
        $shortClass = $treeClass::short_name();
        $permissionCode = 'EDIT_' . strtoupper($shortClass);
        return Permission::checkMember($member, $permissionCode);
    }
}
