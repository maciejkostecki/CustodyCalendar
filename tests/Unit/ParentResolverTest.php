<?php

namespace Tests\Unit;

use App\Services\ParentResolver;
use Tests\TestCase;

class ParentResolverTest extends TestCase
{
    private ParentResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ParentResolver;

        config()->set('custody.parents', [
            'father' => ['label' => 'Father', 'color' => '#2563eb', 'email' => 'dad@example.com'],
            'mother' => ['label' => 'Mother', 'color' => '#db2777', 'email' => 'mum@example.com'],
        ]);
    }

    public function test_resolves_email_to_role(): void
    {
        $this->assertSame('father', $this->resolver->roleForEmail('dad@example.com'));
        $this->assertSame('mother', $this->resolver->roleForEmail('mum@example.com'));
    }

    public function test_email_match_is_case_insensitive(): void
    {
        $this->assertSame('father', $this->resolver->roleForEmail('DAD@Example.com'));
    }

    public function test_unknown_email_returns_null(): void
    {
        $this->assertNull($this->resolver->roleForEmail('stranger@example.com'));
    }

    public function test_other_role(): void
    {
        $this->assertSame('mother', $this->resolver->otherRole('father'));
        $this->assertSame('father', $this->resolver->otherRole('mother'));
    }
}
