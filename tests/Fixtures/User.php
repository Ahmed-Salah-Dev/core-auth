<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

final class User extends Model implements Authenticatable
{
    use AuthenticatableTrait;
    use HasApiTokens;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}