<?php

namespace Deto\Generator\Generators\Scaffold;

use Deto\Generator\Common\CommandData;
use Deto\Generator\Generators\BaseGenerator;
use Deto\Generator\Utils\FileUtil;
use Illuminate\Support\Str;

class ControllerGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $templateType;

    /** @var string */
    private $fileName;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathController;
        $this->templateType = config('deto.laravel_generator.templates', 'deto_generator');
        $this->fileName = $this->commandData->modelName.'Controller.php';
    }

    public function generate()
    {
        $templateName = 'controller';
        $templateData = get_template("scaffold.controller.$templateName", 'deto_generator');
        $templateData = str_replace('$RENDER_TYPE$', 'all()', $templateData);
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);
        $templateData = str_replace(
            '$RESOURCE_FIELDS$',
            implode(','.infy_nl_tab(1, 3), $this->generateResourceFields()),
            $templateData
        );
        FileUtil::createFile($this->path, $this->fileName, $templateData);
        $this->commandData->commandComment("\nController created: ");
        $this->commandData->commandInfo($this->fileName);
    }

    private function generateResourceFields()
    {
        $resourceFields = [];
        foreach ($this->commandData->fields as $field) {
            $resourceFields[] = "'".$field->name."' => \$".Str::camel($this->commandData->modelName).'->'.$field->name;
        }

        return $resourceFields;
    }
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('Controller file deleted: '.$this->fileName);
        }

        if ($this->commandData->getAddOn('datatables')) {
            if ($this->rollbackFile(
                $this->commandData->config->pathDataTables,
                $this->commandData->modelName.'DataTable.php'
            )) {
                $this->commandData->commandComment('DataTable file deleted: '.$this->fileName);
            }
        }
    }
}
