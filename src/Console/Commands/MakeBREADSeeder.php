<?php

namespace AppWorld\VoyagerBreadExtractor\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Translation;

class MakeBREADSeeder extends GeneratorCommand
{
    protected $name = 'voyager:extract';
    protected $description = 'Extract Voyager BREAD from database to SeederFile.';
    protected $type = 'Seeder';

    protected $tableName;
    protected $modelStr;
    protected $model;
    protected $datatype;

    public function handle()
    {
        $this->setDefaultData();
        $name = $this->qualifyClass($this->generateTableName() . "BREADTableSeeder");

        $path = $this->getPath($name);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') ||
             ! $this->option('force')) &&
             $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->sortImports($this->buildClass($name)));

        $this->info($this->type.' created successfully.');
    }

    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);
        $stub = str_replace('DummyTableName',  $this->generateTableName(), $stub);
        $stub = str_replace('DummyDataType',  $this->generateDataType(), $stub);
        $stub = str_replace('DummyDataRow',  $this->generateDataRow(), $stub);
        $stub = str_replace('DummyMenuItem',  $this->generateMenuItem(), $stub);
        $stub = str_replace('DummyPermissions',  $this->generatePermissions(), $stub);
        $stub = str_replace('DummyTranslations',  $this->generateTranslations(), $stub);

        return $stub;
    }

    protected function getStub()
    {
        return  __DIR__ . '/Stubs/make-BREADSeeder.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\..\database\seeds';
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The model name with path separated with dash ( ex: App/Models/Post ).'],
        ];
    }

    protected function setDefaultData(){
        $this->modelStr = (String) Str::of($this->argument('name'))->replace('/', '\\');
        $this->tableName = (new $this->modelStr)->getTable();
        $this->model = new $this->modelStr;
    }

    protected function generateTableName(){
        return Str::studly($this->tableName);
    }

    protected function generateDataType(){
        $this->datatype = DataType::where('model_name', $this->modelStr)->first();

        $model_name = (String) Str::of($this->datatype->model_name)->replace('\\', '\\\\');
        $policy_name = (String) Str::of($this->datatype->policy_name)->replace('\\', '\\\\');
        $controller = (String) Str::of($this->datatype->controller)->replace('\\', '\\\\');
        $details = $this->detailsToStr($this->datatype->details);

        $dataTypeStr = "
        \$dataType = \$this->dataType('slug', '{$this->datatype->slug}');
        if (!\$dataType->exists) {
            \$dataType->fill([
                'name'                  => '{$this->datatype->name}',
                'slug'                  => '{$this->datatype->slug}',
                'display_name_singular' => '{$this->datatype->display_name_singular}',
                'display_name_plural'   => '{$this->datatype->display_name_plural}',
                'icon'                  => '{$this->datatype->icon}',
                'model_name'            => '{$model_name}',
                'policy_name'           => '{$policy_name}',
                'controller'            => '{$controller}',
                'description'           => '{$this->datatype->description}',
                'generate_permissions'  => {$this->datatype->generate_permissions},
                'server_side'           => {$this->datatype->server_side},
                'details'               => {$details}
            ])->save();
        }\n";
        return $dataTypeStr;
    }

    protected function generateDataRow(){
        $className = Str::lower(class_basename($this->modelStr));
        $dataRowStr = "\n\t\t\${$className}DataType = DataType::where('slug', '{$this->datatype->slug}')->firstOrFail();";
        foreach($this->datatype->rows as $row){
            $details = $this->detailsToStr($row->details);
            $dataRowStr .= "
        \$dataRow = \$this->dataRow(\${$className}DataType, '{$row->field}');
        if (!\$dataRow->exists) {
            \$dataRow->fill([
                'field'        => '{$row->field}',
                'type'         => '{$row->type}',
                'display_name' => '{$row->display_name}',
                'required'     => {$row->required},
                'browse'       => {$row->browse},
                'read'         => {$row->read},
                'edit'         => {$row->edit},
                'add'          => {$row->add},
                'delete'       => {$row->delete},
                'details'      => {$details},
                'order'        => {$row->order},
            ])->save();
        }\n";
        }
        return $dataRowStr;
    }

    protected function generateMenuItem(){

        $menu_item_str = "\n\t\t\t//--- Menu Not Found ---";
        $menu_item = MenuItem::where('route', "voyager.{$this->datatype->slug}.index")->first();
        if($menu_item){
            $menu_item_str = "\n\t\tif(config('voyager.bread.add_menu_item')){";

            $parent_id = $menu_item->parent_id ?? 'null';
            $parameters = $this->detailsToStr($menu_item->parameters, false);
            $menu_item_str .= "\n\t\t\t\$menu = Menu::where('name', config('voyager.bread.default_menu'))->firstOrFail();
            \$menuItem = MenuItem::firstOrNew([
                'menu_id' => \$menu->id,
                'title'   => '{$menu_item->title}',
                'url'     => '{$menu_item->url}',
                'route'   => '{$menu_item->route}',
            ]);
            if (!\$menuItem->exists) {
                \$menuItem->fill([
                    'target'     => '{$menu_item->target}',
                    'icon_class' => '{$menu_item->icon_class}',
                    'color'      => '{$menu_item->color}',
                    'parent_id'  => {$parent_id},
                    'order'      => {$menu_item->order},
                    'parameters' => {$parameters}
                ])->save();
            }";

            $menu_item_str .= "\n\t\t}\n";

        }
        return $menu_item_str;
    }

    protected function generatePermissions(){
        $permissions = "\n\t\tif(config('voyager.bread.add_permission')){";
        $permissions .= "\n\t\t\tPermission::generateFor('{$this->tableName}');";
        $permissions .= "\n\t\t\t\$role = Role::where('name', config('voyager.bread.default_role'))->firstOrFail();";
        $permissions .= "\n\t\t\t\$permissions = Permission::where(['table_name' => '{$this->tableName}'])->get()->pluck('id')->all();";
        $permissions .= "\n\t\t\t\$role->permissions()->attach(\$permissions);";
        $permissions .= "\n\t\t}\n";
        return $permissions;
    }

    protected function generateTranslations(){
        $translations_str = "\n\t\t//--- Translations Not Found ---";
        $translations = Translation::where([
            ['foreign_key', '=', $this->datatype->id],
            ['table_name', '=', 'data_types']
        ])->get();

        if(!$translations->count()){
            return $translations_str;
        }

        $translations_str = "\n\t\tif(config('voyager.multilingual.enabled')){";

        $translations_str .= "\n\t\t\t\$datatype = DataType::where('slug', \"{$this->datatype->slug}\")->firstOrFail();\n\t\t\tif (\$datatype->exists) {";
        foreach($translations as $trans){
            $translations_str .= "\n\t\t\t\t\$this->trans('{$trans->locale}', \$this->arr(['{$trans->table_name}', '{$trans->column_name}'], \$datatype->id), '{$trans->value}');";
        }
        $translations_str .= "\n\t\t\t}";

        foreach($this->datatype->rows as $row){
            $translations_str .= "\n\t\t\t\$datarow = DataRow::where('data_type_id', \$datatype->id)->where('field', \"{$row->field}\")->firstOrFail();\n\t\t\tif (\$datarow->exists) {";
                $translations_row = Translation::where([
                    ['foreign_key', '=', $row->id],
                    ['table_name', '=', 'data_rows']
                ])->get();
                if($translations_row->count()){
                    foreach($translations_row as $trans_row){
                        $translations_str .= "\n\t\t\t\t\$this->trans('{$trans_row->locale}', \$this->arr(['{$trans_row->table_name}', '{$trans_row->column_name}'], \$datarow->id), '{$trans_row->value}');";
                    }
                }
            $translations_str .= "\n\t\t\t}";
        }

        $translations_str .= "\n\t\t}";

        return $translations_str;
    }

    protected function detailsToStr($details, $decode = true){
        if(!is_string($details)){
            $details = json_encode($details);
        }
        $details = (String) Str::of($details)->replace('\\', '\\\\');
        if($decode){
            return "json_decode('{$details}')";
        }
        return "'{$details}'";
    }
}
