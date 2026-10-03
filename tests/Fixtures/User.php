<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class User extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}