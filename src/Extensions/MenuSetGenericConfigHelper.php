<?php

namespace Fromholdio\GenericConfig\Extensions;

use Fromholdio\GridFieldLimiter\Forms\GridFieldLimiter;
use Fromholdio\Helpers\GridFields\Forms\GridFieldConfig_Core;
use Fromholdio\SuperLinkerMenus\Model\MenuSet;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\VersionedGridFieldState\VersionedGridFieldState;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;

class MenuSetGenericConfigHelper extends DataExtension
{
    public function getMenuSetGridField(MenuSet $menuSet): GridField
    {
        $menuField = GridField::create(
            $menuSet->getField('Key') . 'Items',
            $menuSet->getCMSTitle() . ' items',
            $menuSet->Items(),
            $menuConfig = GridFieldConfig_Core::create(
                'Sort', 20, true, false, [
                    'MenuSetID' => $menuSet->getField('ID')
                ]
            )
        );

        $menuLimit = (int) $menuSet->getField('Limit');
        if ($menuLimit > 0) {
            $menuLimiter = new GridFieldLimiter(
                $menuLimit, 'before', true
            );
            $menuConfig->addComponent($menuLimiter);
        }

        $menuConfig->addMultiAdder($menuSet->getMenuItemClasses());
        if ($menuLimit > 0) {
            $menuAdder = $menuConfig->getComponentByType(GridFieldAddNewMultiClass::class);
            if ($menuAdder) {
                $menuAdder->setFragment('limiter-before-left');
            }
        }

        $menuConfig->addComponent(new VersionedGridFieldState());
        return $menuField;
    }
}
