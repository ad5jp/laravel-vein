<?php

declare(strict_types=1);

namespace AD5jp\Vein\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * 管理画面にログインするモデル。認証が要るテストでのみ使う。
 *
 * vein は Node を探すとき model_namespaces のクラスを全部 new するため、
 * このクラスも読まれる。Entry / Page を実装していないので Node にはならない。
 */
class TestUser extends Authenticatable
{
    protected $table = 'test_users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];
}
