<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Simplydigital\SchengenCalculator\Models\Concerns\HasSchengenStays;

class TestTraveller extends Model
{
    use HasSchengenStays;

    protected $table = 'test_travellers';

    protected $guarded = [];

    public $timestamps = false;
}