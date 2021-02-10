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
        $templateData = str_replace('$RESOURCE_FIELDS$', implode(',', $this->generateResourceFields()), $templateData);
        $templateData = str_replace('$FILTER_RELATION_WITH$', $this->generateFilterRelations(), $templateData);
        FileUtil::createFile($this->path, $this->fileName, $templateData);
        $this->commandData->commandComment("\nController created: ");
        $this->commandData->commandInfo($this->fileName);
    }
    
    private function generateFilterRelations():string
    {
        $relationWith = [];
        foreach ($this->commandData->relations as $relation) {
            $field = (isset($relation->inputs[0])) ? $relation->inputs[0] : null;
            
            if ($relation->type==='mt1') {
                $singularRelation="";
                if (! empty($relation->relationName)) {
                    $singularRelation = $relation->relationName;
                } elseif (isset($relation->inputs[1])) {
                    $singularRelation = Str::snake(str_replace('_id', '', strtolower($relation->inputs[1])));
                }
                $relationWith[] ="->with('$singularRelation:id,name')";
            }
        }
        return implode("", $relationWith);
    }
    private function generateResourceFields()
    {
        $resourceFields = [];
        foreach ($this->commandData->fields as $field) {
            if (Str::endsWith($field->name, '_id')) {
                $resourceFields[] = "'".Str::replaceLast('_id', '', $field->name)."'";
            }
            $resourceFields[] = "'".$field->name."'";
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
