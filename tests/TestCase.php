<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * View diuji tidak memerlukan asset hasil build Vite.
     *
     * Aset frontend dibangun terpisah melalui `npm run build`, sehingga test
     * tidak bergantung pada keberadaan manifest di folder public.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
