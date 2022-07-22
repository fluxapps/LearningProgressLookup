<?php

namespace srag\Plugins\LearningProgressLookup\Menu;

use ILIAS\GlobalScreen\Scope\MainMenu\Factory\AbstractBaseItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticPluginMainMenuProvider;
use ILIAS\UI\Implementation\Component\Symbol\Icon\Standard;
use ilLearningProgressLookupGUI;
use ilUIPluginRouterGUI;

class Menu extends AbstractStaticPluginMainMenuProvider
{

    public function getStaticTopItems() : array
    {
        return [
            $this->symbol($this->mainmenu->topLinkItem($this->if->identifier($this->plugin->getId() . "_top"))
                ->withTitle($this->plugin->txt("plugin_title"))
                ->withAction($this->dic->ctrl()->getLinkTargetByClass([
                    ilUIPluginRouterGUI::class,
                    ilLearningProgressLookupGUI::class
                ])))
                ->withAvailableCallable(function () : bool {
                    return $this->plugin->isActive();
                })
                ->withVisibilityCallable(function () : bool {
                    return $this->plugin->getAccessManager()->hasCurrentUserViewPermission();
                })
        ];
    }


    public function getStaticSubItems() : array
    {
        return [];
    }


    protected function symbol(AbstractBaseItem $entry) : AbstractBaseItem
    {
        if (method_exists($entry, "withSymbol")) {
            $entry = $entry->withSymbol($this->dic->ui()->factory()->symbol()->icon()->standard(Standard::LRSS, $this->plugin->getPluginName())->withIsOutlined(true));
        }

        return $entry;
    }
}
