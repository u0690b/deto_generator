<?php

namespace Deto\Generator\Generators\Scaffold;

use Deto\Generator\Common\CommandData;
use Deto\Generator\Generators\BaseGenerator;
use Illuminate\Support\Str;

class MenuGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $templateType;

    /** @var string */
    private $menuContents;

    /** @var string */
    private $menuTemplate;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = resource_path('js/Components/') . "AdminMenuList.js";
        $this->templateType = config('deto.laravel_generator.templates', 'deto_generator');

        $this->menuContents = file_get_contents($this->path);

        $templateName = 'menu_template';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $this->menuTemplate = get_template('scaffold.layouts.' . $templateName, $this->templateType);

        $this->menuTemplate = fill_template($this->commandData->dynamicVars, $this->menuTemplate);
    }

    public function generate()
    {

        // $this->menuContents .= $this->menuTemplate.infy_nl();
        // $existingMenuContents = file_get_contents($this->path);
        if (Str::contains($this->menuContents, $this->menuTemplate,)) {
            $this->commandData->commandObj->info('Menu ' . $this->commandData->config->mHumanPlural . ' is already exists, Skipping Adjustment.');
            return;
        }
        $this->menuContents = str_replace(
            "// admin_menu",
            $this->menuTemplate.',' . infy_nl() . "    // admin_menu",
            $this->menuContents
        );
        file_put_contents($this->path, $this->menuContents);
        $this->commandData->commandComment("\n" . $this->commandData->config->mCamelPlural . ' menu added.');
    }

    public function rollback()
    {
        if (Str::contains($this->menuContents, $this->menuTemplate)) {
            file_put_contents($this->path, str_replace($this->menuTemplate, '', $this->menuContents));
            $this->commandData->commandComment('menu deleted');
        }
    }
}
