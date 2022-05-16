<?php

namespace Vinothst94\LaravelSingleSignOn\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('laravel-sso.clientsTable', 'clients');
    }
}
