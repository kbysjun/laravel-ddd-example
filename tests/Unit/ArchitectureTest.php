<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ArchitectureTest extends TestCase
{
    public function test_domain_has_no_framework_or_outer_layer_dependencies(): void
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../app/Domain')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression(
                '/(?:Illuminate|Laravel|App\\\\(?:Http|Infrastructure|UseCases|Models))\\\\/',
                file_get_contents($file->getPathname()),
                $file->getPathname(),
            );
        }
    }

    public function test_use_cases_do_not_depend_on_http_or_persistence(): void
    {
        foreach (glob(__DIR__.'/../../app/UseCases/Post/*.php') as $file) {
            $this->assertDoesNotMatchRegularExpression('/(?:App\\\\(?:Http|Infrastructure|Models)|Illuminate\\\\(?:Http|Database|Support\\\\Facades))\\\\/', file_get_contents($file));
        }
    }
}
