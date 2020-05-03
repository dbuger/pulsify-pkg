# Pulsify

A laravel command utility for creating migrations, controllers and routes from model

Note: this package is still in development stage and needs a lot of improvement specially on the generation of migration limitted to create only. use with extra caution.

### Installation:

```sh 
$ composer require impulse/pulsifier
```

### Configuration:

```sh 
$ php artisan vendor:publish --provider="Impulse\Pulsifier\PulsifierServiceProvider"
```

this will add `pulsifier.php` in the config directory of your laravel app.

### pulsifier.php
```php
<?php 
return [
    'model_path' => "Http\\Models\\"
];
```

if you are using the default directory structure of laravel change this file to this:

```php
<?php 
return [
    'model_path' => "Http\\"
];
```

then execute 
```sh 
$ php artisan config:cache
```
### Usage:

command template: 
```sh
$ php artisan pulsify:model { modelName } { --m }
```
dont add `--m` if you want to ignore generation of migration.

#### Sample call:

```sh
$ php artisan pulsify:model Product --m
```

## Example Integration

To use the command first you must extend your App Model to Impulse\Pulsifier\BaseModel, the Model will still work like a regular laravel model

#### Sample model:
#### Product Model
```php
<?php

namespace App;

use Impulse\Pulsifier\Helpers\Seek;
use Impulse\Pulsifier\Model\BaseModel;

class Product extends BaseModel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        /*
            Search is optional, if set this will add search function to your controller
        */
        $this->searchable = [
            Seek::whereLike('code'),
            Seek::orWhereLike('name'),
            Seek::orWhereHas('category', [
                Seek::whereLike('name')
            ])
        ];
    }

    /*Regular fillable attribute inhereted from Eloquent*/
    protected $fillable = [
        'code',
        'name',
        'product_category_id',
        'unit_id'
    ];

    /*Relationships to be eager load when model is requested from the Generated Controller*/
    protected $eager_loaded_relations = [
        'category',
        'unit',
        'units'
    ];

    /*The command will generate a save() method in the controller and relationships define here will be included in the save method*/
    protected $savable_relations = [
        'units'
    ];

    /*Relationships*/
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'product_units', 'product_id', 'unit_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }
}
```
#### Unit Model

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Impulse\Pulsifier\Helpers\Seek;
use Impulse\Pulsifier\Model\BaseModel;

class Unit extends BaseModel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->searchable = [
            Seek::whereLike('code'),
            Seek::orWhereLike('name'),
        ];
    }

    protected $fillable = [
        'code',
        'name'
    ];
}
```

#### Product Category Model

```php
<?php

namespace App;

use Impulse\Pulsifier\Helpers\Seek;
use Impulse\Pulsifier\Model\BaseModel;

class ProductCategory extends BaseModel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->searchable = [
            Seek::whereLike('code'),
            Seek::orWhereLike('name'),
        ];
    }
    protected $fillable = [
        'code',
        'name'
    ];
}
```

### Example Output

After the command execution a controller, routes & migration(optional) will be generated for the Model use in the command.

#### Controller

```php
<?php

namespace App\Http\Controllers;

use Impulse\Pulsifier\Controller\BaseController as PulsifierBaseController;
use Illuminate\Http\Request;
use App\Product;
use App\Unit;

class ProductController extends PulsifierBaseController
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->relationShips = ['category', 'unit', 'units'];
    }

    public function index()
    {
        $query = Product::with($this->relationShips)
            ->when(!empty($this->searchTerm), function ($query) {
                $query->where('code', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('category', function ($category) {
                        $category->where('name', 'like', '%' . $this->searchTerm . '%');
                    });
            });
        $products = ($this->perPage != 0) ? $query->paginate($this->perPage) : $query->get();
        return response($products);
    }

    public function save()
    {
        $data = $this->request->all();
        $id = isset($data['id']) ? $data['id'] : -1;

        $product = Product::updateOrCreate(
            ['id' => $id],
            $data
        );

        if (isset($data['units']) && count($data['units']) != 0) {
            $unit = $product->units->pluck('pivot');
            $pivoted_ids = $unit->pluck('unit_id');
            $pivoted_ids->concat(collect($data['units'])->pluck('unit_id'));
            $product->units()->sync($pivoted_ids->unique()->all());
        }


        if (empty($product))
            return response("An error occur during save", 500);

        return $this->get($product->id);
    }

    public function get($id)
    {
        $product = Product::with($this->relationShips)->find($id);
        if (empty($product))
            return response("Record not found", 404);
        return response($product);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (empty($product))
            return response("Record not found", 404);
        if (!$product->delete())
            return response("An error occur during delete", 500);
        return response("Record deleted");
    }
}
```

#### Route
```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('products')->name('products')->group(function(){
    Route::get('/','ProductController@index')->name('index');
    Route::get('/get/{id}','ProductController@get')->name('get')->where('id', '[0-9]+');
    Route::delete('/destroy/{id}','ProductController@destroy')->name('destroy')->where('id', '[0-9]+');
    Route::post('/save','ProductController@save')->name('save');
});
```

