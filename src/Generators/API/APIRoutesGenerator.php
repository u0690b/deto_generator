<?php

namespace Deto\Generator\Generators\API;

use Illuminate\Support\Str;
use Deto\Generator\Common\CommandData;
use Deto\Generator\Generators\BaseGenerator;

class APIRoutesGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $routeContents;

    /** @var string */
    private $routesTemplate;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathApiRoutes;

        $this->routeContents = file_get_contents($this->path);

        if (!empty($this->commandData->config->prefixes['route'])) {
            $routesTemplate = get_template('api.routes.prefix_routes', 'deto_generator');
        } else {
            $routesTemplate = get_template('api.routes.routes', 'deto_generator');
        }

        $this->routesTemplate = fill_template($this->commandData->dynamicVars, $routesTemplate);
    }

    public function generate()
    {
        if (Str::contains($this->routeContents, $this->routesTemplate)) {
            $this->commandData->commandObj->info('API Route '.$this->commandData->config->mPlural.' is already exists, Skipping Adjustment.');
            return;
        }
        $this->routeContents .= "\n\n".$this->routesTemplate;
        file_put_contents($this->path, $this->routeContents);
        $this->commandData->commandComment("\n".$this->commandData->config->mCamelPlural.' api routes added.');
    }

    public function rollback()
    {
        if (Str::contains($this->routeContents, $this->routesTemplate)) {
            $this->routeContents = str_replace($this->routesTemplate, '', $this->routeContents);
            file_put_contents($this->path, $this->routeContents);
            $this->commandData->commandComment('api routes deleted');
        }
    }
}
