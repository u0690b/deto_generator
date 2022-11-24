<?php

namespace Deto\Generator\Generators\Scaffold;

use Deto\Generator\Common\CommandData;
use Deto\Generator\Generators\BaseGenerator;
use Deto\Generator\Generators\ViewServiceProviderGenerator;
use Deto\Generator\Utils\FileUtil;
use Deto\Generator\Utils\HTMLFieldGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ViewGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $templateType;

    /** @var array */
    private $htmlFields;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathViews;
        $this->templateType = config('deto.laravel_generator.templates', 'deto_generator');
    }

    public function generate()
    {
        if (!file_exists($this->path)) {
            mkdir($this->path, 0755, true);
        }

        $htmlInputs = Arr::pluck($this->commandData->fields, 'htmlInput');
        if (in_array('file', $htmlInputs)) {
            $this->commandData->addDynamicVariable('$FILES$', ", 'files' => true");
        }

        $this->commandData->commandComment("\nGenerating Views...");

        if ($this->commandData->getOption('views')) {
            $viewsToBeGenerated = explode(',', $this->commandData->getOption('views'));

            if (in_array('index', $viewsToBeGenerated)) {
                // $this->generateTable();
                $this->generateIndex();
            }

            // if (count(array_intersect(['create', 'update'], $viewsToBeGenerated)) > 0) {
            //     $this->generateFields();
            // }

            if (in_array('create', $viewsToBeGenerated)) {
                $this->generateCreate();
            }

            if (in_array('edit', $viewsToBeGenerated)) {
                $this->generateUpdate();
            }

            // if (in_array('show', $viewsToBeGenerated)) {
            //     $this->generateShowFields();
            //     $this->generateShow();
            // }
        } else {
            // $this->generateTable();
            $this->generateIndex();
            // $this->generateFields();
            $this->generateCreate();
            $this->generateUpdate();
            // $this->generateShowFields();
            // $this->generateShow();
        }

        $this->commandData->commandComment('Views created: ');
    }

    private function generateTable()
    {

        $templateData = $this->generateBladeTableBody();

        FileUtil::createFile($this->path, 'table.blade.php', $templateData);

        $this->commandData->commandInfo('table.blade.php created');
    }

    private function generateDataTableBody()
    {
        $templateData = get_template('scaffold.views.datatable_body', $this->templateType);

        return fill_template($this->commandData->dynamicVars, $templateData);
    }

    private function generateDataTableActions()
    {
        $templateName = 'datatables_actions';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $templateData = get_template('scaffold.views.' . $templateName, $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'datatables_actions.blade.php', $templateData);

        $this->commandData->commandInfo('datatables_actions.blade.php created');
    }

    private function generateFilter_Relation_Fields($templateData)
    {
        $bodyFields = [];
        $filters = [];
        $templateFieldData = get_template('scaffold.views.filter_field', $this->templateType);
        $MY_SELECT_CMP = "";
        $MY_SELECT_IMPORT = "";

        foreach ($this->commandData->fields as $field) {
            if (!Str::endsWith($field->name, '_id')) {
                continue;
            }
            $filters[] = "$field->name: null,";
            // $FILTER_RELATION_MODEL_NAME = Str::camel(Str::plural(str_replace('_id', '', strtolower($field->name))));
            // $this->commandData->addDynamicVariable('$FILTER_RELATION_MODEL_NAME$', $FILTER_RELATION_MODEL_NAME);
            // $bodyFields[] = fill_template_with_field_data(
            //     $this->commandData->dynamicVars,
            //     $this->commandData->fieldNamesMapping,
            //     $templateFieldData,
            //     $field
            // );

            $FILTER_RELATION_MODEL_NAME_SNAKE = Str::snake(Str::plural(str_replace('_id', '', strtolower($field->name))));
            $this->commandData->addDynamicVariable('$FILTER_RELATION_MODEL_NAME_SNAKE$', $FILTER_RELATION_MODEL_NAME_SNAKE);
            $bodyFields[] = fill_template_with_field_data(
                $this->commandData->dynamicVars,
                $this->commandData->fieldNamesMapping,
                $templateFieldData,
                $field
            );

            $MY_SELECT_IMPORT = "import MySelect from '@/Components/MySelect.vue'";
            $MY_SELECT_CMP = "MySelect,";
        }

        $templateData = str_replace('$MY_SELECT_CMP$', $MY_SELECT_CMP, $templateData);
        $templateData = str_replace('$MY_SELECT_IMPORT$', $MY_SELECT_IMPORT, $templateData);

        $templateData = str_replace('$FILTER_RELATION_FIELDS$', implode(infy_nl_tab(1, 2, 2), $filters), $templateData);
        return str_replace('$FILTER_RELATION_FIELDS_BODY$', implode("\n", $bodyFields), $templateData);
    }
    private function generateBladeTableBody($templateData)
    {
        $templateName = "blade_table_body";
        $tableFields = $this->generateTableHeaderFields();

        // $templateData = get_template('scaffold.views.'.$templateName, $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$FIELD_HEADERS$', $tableFields, $templateData);

        $cellFieldTemplate = get_template('scaffold.views.table_cell', $this->templateType);
        $templateData = $this->generateFilter_Relation_Fields($templateData);

        $tableBodyFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }
            if (Str::endsWith($field->name, '_id')) {
                $cellFieldTemplate1 = str_replace('$FIELD_NAME$', Str::replaceLast('_id', '', $field->name) . ' ? $MODEL_NAME_CAMEL$.$FIELD_NAME$ : \'\'', $cellFieldTemplate);
                $filledcellFieldTemplate = str_replace('$FIELD_NAME$', Str::replaceLast('_id', '.name', $field->name), $cellFieldTemplate1);
                $tableBodyFields[] = fill_template_with_field_data(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $filledcellFieldTemplate,
                    $field
                );
            } else {
                $tableBodyFields[] = fill_template_with_field_data(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $cellFieldTemplate,
                    $field
                );
            }
        }

        $tableBodyFields = implode("", $tableBodyFields);

        return str_replace('$FIELD_BODY$', $tableBodyFields, $templateData);
    }

    private function generateJSTableHeaderFields()
    {
        $fields = '';
        foreach ($this->commandData->fields as $field) {
            if (in_array($field->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fields .= '<th scope="col">' . str_replace("'", '', $field->name) . '</th>';
        }

        return $fields;
    }

    private function generateTableHeaderFields()
    {
        $templateName = 'table_header';

        $localized = false;
        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
            $localized = true;
        }

        $headerFieldTemplate = get_template('scaffold.views.' . $templateName, $this->templateType);

        $headerFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }

            if ($localized) {
                /**
                 * Replacing $FIELD_NAME$ before fill_template_with_field_data_locale() otherwise also
                 * $FIELD_NAME$ get replaced with @lang('models/$modelName.fields.$value')
                 * and so we don't have $FIELD_NAME$ in table_header_locale.stub
                 * We could need 'raw' field name in header for example for sorting.
                 * We still have $FIELD_NAME_TITLE$ replaced with @lang('models/$modelName.fields.$value').
                 *
                 * @see issue https://github.com/DetoLabs/deto_generator/issues/887
                 */


                $preFilledHeaderFieldTemplate = str_replace('$FIELD_NAME$', $field->name, $headerFieldTemplate);

                $headerFields[] = $fieldTemplate = fill_template_with_field_data_locale(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $preFilledHeaderFieldTemplate,
                    $field
                );
            } else {
                $headerFields[] = $fieldTemplate = fill_template_with_field_data(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $headerFieldTemplate,
                    $field
                );
            }
        }

        return implode("\n          ", $headerFields);
    }

    private function generateIndex()
    {
        $templateName = ($this->commandData->jqueryDT()) ? 'js_index' : 'index';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $templateData = get_template('scaffold.views.' . $templateName, $this->templateType);
        $templateData = str_replace('$RESOURCE_FIELDS$', implode(',', $this->generateHeaderFields()), $templateData);
        $templateData = $this->generateBladeTableBody($templateData);
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        if ($this->commandData->getAddOn('datatables')) {
            $templateData = str_replace('$PAGINATE$', '', $templateData);
        } else {
            $paginate = $this->commandData->getOption('paginate');

            if ($paginate) {
                $paginateTemplate = get_template('scaffold.views.paginate', $this->templateType);

                $paginateTemplate = fill_template($this->commandData->dynamicVars, $paginateTemplate);

                $templateData = str_replace('$PAGINATE$', $paginateTemplate, $templateData);
            } else {
                $templateData = str_replace('$PAGINATE$', '', $templateData);
            }
        }

        FileUtil::createFile($this->path, 'Index.vue', $templateData);

        $this->commandData->commandInfo('Index.vue created');
    }

    private function generateFields($templateName)
    {
        // $templateName = 'fields';

        $localized = false;
        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
            $localized = true;
        }

        $this->htmlFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inForm) {
                continue;
            }

            $validations = explode('|', $field->validations);
            $minMaxRules = '';
            foreach ($validations as $validation) {
                if (!Str::contains($validation, ['max:', 'min:'])) {
                    continue;
                }

                $validationText = substr($validation, 0, 3);
                $sizeInNumber = substr($validation, 4);

                $sizeText = ($validationText === 'min') ? 'minlength' : 'maxlength';
                if ($field->htmlType === 'number') {
                    $sizeText = $validationText;
                }

                $size = ",'$sizeText' => $sizeInNumber";
                $minMaxRules .= $size;
            }
            $this->commandData->addDynamicVariable('$SIZE$', $minMaxRules);

            $fieldTemplate = HTMLFieldGenerator::generateHTML($field, $this->templateType, $localized);
            if (Str::endsWith($field->name, '_id')) {
                $fieldTemplate = get_template('scaffold.fields.my_select', $this->templateType);
                $FILTER_RELATION_MODEL_NAME_SNAKE = Str::snake(Str::plural(str_replace('_id', '', strtolower($field->name))));
                $this->commandData->addDynamicVariable('$FILTER_RELATION_MODEL_NAME_SNAKE$', $FILTER_RELATION_MODEL_NAME_SNAKE);
                if ($templateName == "create") {
                    $fieldTemplate = str_replace('$FIELD_NAME_OBJECT$', 'null', $fieldTemplate);
                } else {
                    $fieldTemplate = str_replace('$FIELD_NAME_OBJECT$', "data." . Str::replaceLast('_id', '', $field->name), $fieldTemplate);
                }
            }
            if ($field->htmlType === 'selectTable') {
                $inputArr = explode(',', $field->htmlValues[1]);
                $columns = '';
                foreach ($inputArr as $item) {
                    $columns .= "'$item'" . ',';  //e.g 'email,id,'
                }
                $columns = substr_replace($columns, '', -1); // remove last ,

                $htmlValues = explode(',', $field->htmlValues[0]);
                $selectTable = $htmlValues[0];
                $modalName = null;
                if (count($htmlValues) === 2) {
                    $modalName = $htmlValues[1];
                }

                $tableName = $this->commandData->config->tableName;
                $viewPath = $this->commandData->config->prefixes['view'];
                if (!empty($viewPath)) {
                    $tableName = $viewPath . '.' . $tableName;
                }

                $variableName = Str::singular($selectTable) . 'Items'; // e.g $userItems

                $fieldTemplate = $this->generateViewComposer($tableName, $variableName, $columns, $selectTable, $modalName);
            }

            if (!empty($fieldTemplate)) {
                $fieldTemplate = fill_template_with_field_data(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $fieldTemplate,
                    $field
                );
                $this->htmlFields[] = $fieldTemplate;
            }
        }

        $templateData = get_template('scaffold.views.' . $templateName, $this->templateType);
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$FIELDS$', implode("\n          ", $this->htmlFields), $templateData);
        $templateData = str_replace(
            '$FORM_FIELDS$',
            implode(",\n        ", $this->generateResourceFields()),
            $templateData
        );
        $templateData = str_replace(
            '$INIT_FORM_FIELDS$',
            implode(',' . infy_nl_tab(1, 2, 2), $this->generateInitResourceFields()),
            $templateData
        );
        $templateData = str_replace(
            '$IMPORT_INPUT$',
            implode("\n", $this->generateImport()),
            $templateData
        );
        $templateData = str_replace(
            '$IMPORT_COMPONENT$',
            implode(",\n    ", $this->generateComponent()),
            $templateData
        );

        FileUtil::createFile($this->path, ucfirst($templateName) . '.vue', $templateData);
        $this->commandData->commandInfo('field.blade.php created');
    }
    private function generateImport()
    {
        $resourceFields = [];
        foreach ($this->commandData->fields as $field) {
            $resourceFields[] =    HTMLFieldGenerator::generateImport($field);
        }
        return array_unique($resourceFields);
    }
    private function generateComponent()
    {
        $resourceFields = [];
        foreach ($this->commandData->fields as $field) {
            $resourceFields[] =    HTMLFieldGenerator::generateComponents($field);
        }

        return array_unique($resourceFields);
    }
    private function generateHeaderFields()
    {
        $resourceFields = [];
        foreach ($this->commandData->fields as $field) {
            if (in_array($field->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            if (Str::endsWith($field->name, '_id')) {
                $resourceFields[] = "'" . Str::replaceLast('_id', '', $field->name) . ".name':'" . $field->description . "'";
            }
            $resourceFields[] = "'" . $field->name . "':'" . $field->description . "'";
        }

        return $resourceFields;
    }
    private function generateResourceFields()
    {
        $resourceFields = [];
        foreach ($this->commandData->fields as $field) {
            $resourceFields[] = "" . $field->name . ": this.data." . $field->name;
        }

        return $resourceFields;
    }
    private function generateInitResourceFields()
    {
        $resourceFields = [];
        foreach ($this->commandData->fields as $field) {
            $resourceFields[] = "" . $field->name . ": null";
        }

        return $resourceFields;
    }

    private function generateViewComposer($tableName, $variableName, $columns, $selectTable, $modelName = null)
    {
        $templateName = 'scaffold.fields.select';
        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }
        $fieldTemplate = get_template($templateName, $this->templateType);

        $viewServiceProvider = new ViewServiceProviderGenerator($this->commandData);
        $viewServiceProvider->generate();
        $viewServiceProvider->addViewVariables($tableName . '.fields', $variableName, $columns, $selectTable, $modelName);

        $fieldTemplate = str_replace(
            '$INPUT_ARR$',
            '$' . $variableName,
            $fieldTemplate
        );

        return $fieldTemplate;
    }

    private function generateCreate()
    {
        $templateName = 'create';

        $this->generateFields($templateName);

        $this->commandData->commandInfo('create.blade.php created');
    }

    private function generateUpdate()
    {
        $templateName = 'edit';

        $this->generateFields($templateName);

        $this->commandData->commandInfo('edit.blade.php created');
    }

    private function generateShowFields()
    {
        $templateName = 'show_field';
        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }
        $fieldTemplate = get_template('scaffold.views.' . $templateName, $this->templateType);

        $fieldsStr = '';

        foreach ($this->commandData->fields as $field) {
            if (!$field->inView) {
                continue;
            }
            $singleFieldStr = str_replace(
                '$FIELD_NAME_TITLE$',
                Str::title(str_replace('_', ' ', $field->description)),
                $fieldTemplate
            );
            $singleFieldStr = str_replace('$FIELD_NAME$', $field->name, $singleFieldStr);
            $singleFieldStr = fill_template($this->commandData->dynamicVars, $singleFieldStr);

            $fieldsStr .= $singleFieldStr . "\n\n";
        }

        FileUtil::createFile($this->path, 'show_fields.blade.php', $fieldsStr);
        $this->commandData->commandInfo('show_fields.blade.php created');
    }

    private function generateShow()
    {
        $templateName = 'show';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $templateData = get_template('scaffold.views.' . $templateName, $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'show.blade.php', $templateData);
        $this->commandData->commandInfo('show.blade.php created');
    }

    public function rollback($views = [])
    {
        $files = [
            'table.blade.php',
            'Index.vue',
            'fields.blade.php',
            'Create',
            'Edit',
            'show.blade.php',
            'show_fields.blade.php',
        ];

        if (!empty($views)) {
            $files = [];
            foreach ($views as $view) {
                $files[] = $view . '.blade.php';
            }
        }

        if ($this->commandData->getAddOn('datatables')) {
            $files[] = 'datatables_actions.blade.php';
        }

        foreach ($files as $file) {
            if ($this->rollbackFile($this->path, $file)) {
                $this->commandData->commandComment($file . ' file deleted');
            }
        }
    }
}
