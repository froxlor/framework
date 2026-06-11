<?php

namespace Tests\Feature;

use Froxlor\Core\Support\FroxlorVersion;
use Tests\TestCase;

class FroxlorVersionTest extends TestCase
{
    public function test_release_version_comes_from_froxlor_config(): void
    {
        config(['froxlor.release_version' => '3.0.7']);

        $this->assertSame('3.0.7', FroxlorVersion::release());
        $this->assertSame('3.0', FroxlorVersion::releaseSeries());
        $this->assertSame('Froxlor/3.0.7', FroxlorVersion::userAgent());
    }
}
