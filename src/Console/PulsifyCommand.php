<?php

namespace Impulse\Pulsifier\Console;
use Impulse\Pulsifier;
use Impulse\Pulsifier\Helpers\Seek;
use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Impulse\Pulsifier\ClassModelAutoLoader;

class PulsifyCommand extends Command
{
    protected $name;
    protected $createMigration = false;
    protected $reflection;
    protected $instance;

    protected $signature = 'pulsify:model {name="Model name"} {--m}';
    protected $description = 'Scaffold model using impulse package, generates: Migration, API Route & Controller';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->name = $this->argument('name');
        $this->createMigration = (bool)$this->option('m');
        $this->reflection = "App\\".$this->name;
    
        $modelPath = Config::get("pulsifier.model_path");

        spl_autoload_register(function ($className) use ($modelPath){
            $className = str_replace("App\\", DIRECTORY_SEPARATOR, $className);
            include_once __DIR__."../../Http/Traits/HasPulseAttribute.php";
            include_once __DIR__."../../Http/Models/BaseModel.php";
            include_once app_path($modelPath.$className.".php");
        });

        $this->instance = new $this->reflection();

        if(!file_exists(app_path($modelPath)."{$this->name}.php"))
        {
            $this->error("Pulsify failed: Model {$this->name} not found. Unable to pulsify undefined model.");
            return;
        }

        if(get_parent_class($this->instance) != "Impulse\\Pulsifier\\Model\\BaseModel")
        {
            $this->error("Pulsify failed: Model not a child of BaseModel.");
            return;
        }

        if(file_exists(app_path("Http\Controllers\\{$this->name}Controller.php")))
        {
            if($this->confirm("Controller already exist, do you wish to override controller?",true)){
                unlink(app_path("Http\Controllers\\{$this->name}Controller.php"));
                $this->makeController($this->name);
            }
        }
        else{
            $this->makeController($this->name);
        }
    
        $this->makeRoutes($this->name);

        if($this->createMigration){
            $this->makeMigration($this->name);
        }

        $this->info("--- Pulsification complete ---");

    }

   

    public function getStub($stub){
        return file_get_contents(__DIR__."\\..\\resources\\stubs\\".$stub.".stub");
    }

    public function makeMigration($name){
        $file_name = date("Y_m_d_his")."_create_".strtolower(Str::snake(Str::plural($name)))."_table.php";

        if(!file_exists(app_path("../database/migrations/${file_name}"))) {
            $this->info("Creating {$name} migration");
            $template = str_replace(
                [
                    '{{modelNamePluralize}}',
                    '{{modelNameLowercasePluralize}}',
                    '{{bluePrints}}'
                ],
                [
                    Str::plural($name),
                    strtolower(Str::snake(Str::plural($name))),
                    $this->makeMigrationBlueprint()
                ],
                $this->getStub("Migration")
            );
     
            $this->info(" - Writing migration..");
            file_put_contents(app_path("../database/migrations/${file_name}"), $template);
            $this->info(" - Migration created.");
        }
   
    }

    public function makeController($name){

        $this->info("Creating {$name}Controller...");
        $savable = $this->makeSavableRelations($name);
        $template = str_replace(
            [
                '{{modelName}}',
                '{{modelNameLowercasePluralize}}',
                '{{modelNameLowercase}}',
                '{{relationShips}}',
                '{{searchMethod}}',
                '{{savableRelationStub}}',
                '{{savableRelationModels}}'
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                strtolower($name),
                $this->instance->getEagerLoadedRelationsString(),
                $this->instance->setSearchMethod(),
                count($savable) != 0 ? $savable['stubs'] : '',
                count($savable) != 0 ? $savable['uses'] : ''
            ],
            $this->getStub("Controller")
        );

        //TODO: Format text 
        //Seek::ReformatWrite($template);
        
        // if(!file_exists(app_path("Http/Controllers/BaseController.php"))) {
        //     $this->info("Creating BaseController: execute if BaseController not found.");
        //     file_put_contents(app_path("Http/Controllers/BaseController.php"), $this->getStub("BaseController"));
        //     $this->info(" - BaseController created.");
        // }

        file_put_contents(app_path("Http/Controllers/{$name}Controller.php"),$template);
        $this->info(" - {$name}Controller created.");
    }

    public function makeRoutes($name){

        $ocurrence = Seek::getRouteBlock(strtolower(Str::plural($name)));
        if(!empty($ocurrence))
            return;
        $this->info("Creating {$name} API routes...");
        $template = str_replace(
            [
                '{{modelName}}',
                '{{modelNameLowercasePluralize}}'
            ],
            [
                $name,
                strtolower(Str::snake(Str::plural($name)))
            ],
            $this->getStub("Route")
        );
        file_put_contents(app_path("../routes/api.php"),$template,FILE_APPEND);
        $this->info(" - API {$name} routes created");
    }

    private function makeMigrationBlueprint(){
        $stubs = "";
        $attributes = $this->instance->getAllAttributes();
        foreach($attributes as $attribute)
        {
            if(strpos($attribute,"id")){
                $stub = PS_MIGRATION_INTEGER;
            }
            else if(strpos($attribute,"date") || strpos($attribute,"at"))
            {
                $stub = PS_MIGRATION_DATE_TIME;
            }
            else
            {
                $stub = PS_MIGRATION_STRING_DEFAULT_EMPTY;
            }
            $template =  str_replace(
                [
                    '{{field}}'
                ],
                [
                    $attribute
                ],
                $stub
            );
            $stubs .= "\t\t\t".$template.PHP_EOL;
        }
        return $stubs;
    }

    private function makeSavableRelations($name){
        $uses = "";
        $stubs = "";
        $stub = "";
        $savable_relations = $this->instance->getSavableRelations();
        if(count($savable_relations) == 0)
            return [];
        foreach ($savable_relations as $relation){
            $uses .= "use App\\".$relation['class_name'].";".PHP_EOL;
            switch ($relation['type']){
                case PS_BELONGS_TO_MANY_SAVABLE_RELATION:
                    $stub = $this->getStub("BelongsToManySavableRelation");
                    break;
                case PS_HAS_MANY_SAVABLE_RELATION:
                    $stub = $this->getStub("HasManySavableRelation");
                    break;
                default:
                    break;
            }

            $template =  str_replace(
                [
                    '{{modelNameLowercase}}',
                    '{{methodName}}',
                    '{{relatedClassNameLowercase}}',
                    '{{relatedClassName}}'
                ],
                [
                    strtolower($name),
                    $relation['method_name'],
                    strtolower($relation['class_name']),
                    $relation['class_name']
                ],
                $stub
            );
            $stubs .= $template.PHP_EOL;
        }
        return ['uses'=>$uses, 'stubs' => $stubs];
    }
}
