<?php

namespace Visual1\OptionsOrdering\Tests;

use Visual1\OptionsOrdering\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
