<?php

namespace Vinothst94\LaravelSingleSignOn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientUser extends Model
{
    use SoftDeletes;
    /**
    * Get the table associated with the model.
    *
    * @return string
    */
    public function getTable()
    {
        return config('laravel-sso.clientUserTable', 'client_user');
    }
}
