<?php

namespace Deto\Generator\Generators\API;

use Deto\Generator\Common\CommandData;
use Deto\Generator\Generators\BaseGenerator;
use Deto\Generator\Utils\FileUtil;
use Illuminate\Support\Str;

class APIControllerGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $fileName;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathApiController;
        $this->fileName = $this->commandData->modelName.'APIController.php';
    }

    public function generate()
    {
        $templateName = 'model_api_controller';
       

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        if ($this->commandData->getOption('resources')) {
            $templateName .= '_resource';
        }

        $templateData = get_template("api.controller.$templateName", 'deto_generator');
        $templateData = str_replace('$FILTER_RELATION_WITH$', $this->generateFilterRelations(), $templateData);
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);
        $templateData = $this->fillDocs($templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandComment("\nAPI Controller created: ");
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
    private function fillDocs($templateData)
    {
        $methods = ['controller', 'index', 'store', 'show', 'update', 'destroy'];

        if ($this->commandData->getAddOn('swagger')) {
            $templatePrefix = 'controller_docs';
            $templateType = 'swagger-generator';
        } else {
            $templatePrefix = 'api.docs.controller';
            $templateType = 'deto_generator';
        }

        foreach ($methods as $method) {
            $key = '$DOC_'.strtoupper($method).'$';
            $docTemplate = get_template($templatePrefix.'.'.$method, $templateType);
            $docTemplate = fill_template($this->commandData->dynamicVars, $docTemplate);
            $templateData = str_replace($key, $docTemplate, $templateData);
        }

        return $templateData;
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('API Controller file deleted: '.$this->fileName);
        }
    }
}
