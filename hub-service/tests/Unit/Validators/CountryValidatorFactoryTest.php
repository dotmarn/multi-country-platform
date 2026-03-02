<?php

namespace Tests\Unit\Validators;

use App\Validators\CountryValidatorFactory;
use App\Validators\GermanyValidator;
use App\Validators\USAValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CountryValidatorFactoryTest extends TestCase
{
    private CountryValidatorFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new CountryValidatorFactory();
    }

    public function test_it_creates_usa_validator(): void
    {
        $validator = $this->factory->make('USA');
        $this->assertInstanceOf(USAValidator::class, $validator);
    }

    public function test_it_creates_germany_validator(): void
    {
        $validator = $this->factory->make('Germany');
        $this->assertInstanceOf(GermanyValidator::class, $validator);
    }

    public function test_it_throws_for_unsupported_country(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->make('France');
    }

    public function test_supports_returns_true_for_known_countries(): void
    {
        $this->assertTrue($this->factory->supports('USA'));
        $this->assertTrue($this->factory->supports('Germany'));
    }

    public function test_supports_returns_false_for_unknown_countries(): void
    {
        $this->assertFalse($this->factory->supports('France'));
        $this->assertFalse($this->factory->supports(''));
    }

    public function test_supported_countries_returns_all_keys(): void
    {
        $countries = $this->factory->supportedCountries();
        $this->assertContains('USA', $countries);
        $this->assertContains('Germany', $countries);
        $this->assertCount(2, $countries);
    }
}
