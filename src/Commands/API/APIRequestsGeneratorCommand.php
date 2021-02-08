<?php

namespace Deto\Generator\Commands\API;

use Deto\Generator\Commands\BaseCommand;
use Deto\Generator\Common\CommandData;
use Deto\Generator\Generators\API\APIRequestGenerator;

class APIRequestsGeneratorCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'deto.api:requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an api request command';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandData = new CommandData($this, CommandData::$COMMAND_TYPE_API);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $controllerGenerator = new APIRequestGenerator($this->commandData);
        $controllerGenerator->generate();

        $this->performPostActions();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array_merge(parent::getOptions(), []);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array_merge(parent::getArguments(), []);
    }
}
