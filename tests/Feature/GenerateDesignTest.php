<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Design\Generation\DecisionSource;
use App\Design\Generation\DesignGenerator;
use App\Design\Standard;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeDecisionSource;
use Tests\TestCase;

class GenerateDesignTest extends TestCase
{
    private FakeDecisionSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = new FakeDecisionSource(FakeDecisionSource::sample());
        $this->app->instance(DecisionSource::class, $this->source);
        config()->set('design.keep_raw', false);
        config()->set('design.model', 'claude-test-model');
    }

    protected function tearDown(): void
    {
        foreach (File::glob(Design::directory().'/tidewater*.json') as $path) {
            File::delete($path);
        }
        parent::tearDown();
    }

    #[Test]
    public function the_generator_sends_the_standard_and_the_brief_and_returns_a_design(): void
    {
        $design = app(DesignGenerator::class)->generate([
            'client' => 'Tidewater Port Services',
            'product' => 'Berth scheduling for a container port. Used all shift by dispatchers.',
            'shell' => 'sidebar',
        ]);

        $this->assertSame('tidewater-port-services', $design->slug);
        $this->assertSame('claude-test-model', $this->source->lastModel);
        $prompt = $this->source->lastSystem[0]['text'];
        $this->assertStringContainsString('# 01. Principles', $prompt);
        $this->assertStringNotContainsString('# 00. Audit', $prompt);

        // Cut at the marker: the rules the model reasons from are in, the rules
        // only a screen-builder acts on are out. See docs/cut-sheet.md.
        $this->assertStringNotContainsString(Standard::PROMPT_END, $prompt);
        foreach (['Accent bar', 'IBM Plex Sans', 'Comfortable is for apps used occasionally', 'hue must sit at least 30 degrees'] as $kept) {
            $this->assertStringContainsString($kept, $prompt, "the generator still needs: {$kept}");
        }
        foreach (['flux:sidebar', 'prefers-reduced-motion', '--color-zinc-50', 'Skeleton shimmer'] as $cut) {
            $this->assertStringNotContainsString($cut, $prompt, "the generator should not be paying for: {$cut}");
        }
        $this->assertLessThan(strlen(Standard::readme()) * 12, strlen($prompt), 'the prompt should stay short');
        $this->assertSame(['type' => 'ephemeral'], $this->source->lastSystem[1]['cacheControl']);
        $this->assertStringContainsString('Tidewater Port Services', $this->source->lastUser);
        $this->assertSame('#1d6f8a', $design->decision('accent'));
        $this->assertSame('claude-test-model', $design->generator['model']);
        $this->assertTrue($design->tokens()['contrast']['passes']);
        $this->assertFalse(Design::exists($design->slug), 'generate() must not save; the caller does');
    }

    #[Test]
    public function the_command_saves_a_design_and_the_dry_run_does_not(): void
    {
        $this->artisan('design:generate', ['client' => 'Tidewater', 'product' => 'Berth scheduling for a port, used all shift.', '--dry-run' => true])
            ->assertSuccessful();
        $this->assertFalse(Design::exists('tidewater'));

        $this->artisan('design:generate', ['client' => 'Tidewater', 'product' => 'Berth scheduling for a port, used all shift.', '--shell' => 'top-nav'])
            ->assertSuccessful();
        $this->assertTrue(Design::exists('tidewater'));

        $saved = Design::load('tidewater');
        $this->assertSame('top-nav', $saved->brief['shell']);
        $this->assertSame(3, $saved->decisions['pairing']['value']);
        $this->assertNotNull($saved->generator);
    }

    #[Test]
    public function a_second_design_for_the_same_client_gets_a_new_slug(): void
    {
        $this->artisan('design:generate', ['client' => 'Tidewater', 'product' => 'Berth scheduling for a port, used all shift.'])->assertSuccessful();
        $this->artisan('design:generate', ['client' => 'Tidewater', 'product' => 'Berth scheduling for a port, used all shift.'])->assertSuccessful();

        $this->assertTrue(Design::exists('tidewater'));
        $this->assertTrue(Design::exists('tidewater-2'));
    }

    #[Test]
    public function the_form_validates_then_generates_and_redirects_to_the_design(): void
    {
        Livewire::test('pages::designs.create')
            ->set('client', '')
            ->call('generate')
            ->assertHasErrors(['client', 'product']);

        $this->assertSame(0, $this->source->calls);

        Livewire::test('pages::designs.create')
            ->set('client', 'Tidewater Port Services')
            ->set('product', 'Berth scheduling for a container port. Used all shift by dispatchers.')
            ->set('shell', 'top-nav')
            ->set('brand_colour', '#1d6f8a')
            ->call('generate')
            ->assertHasNoErrors()
            ->assertRedirect(route('designs.show', 'tidewater-port-services'));

        $this->assertSame(1, $this->source->calls);
        $this->assertSame('top-nav', Design::load('tidewater-port-services')->brief['shell']);
    }

    #[Test]
    public function regenerate_on_the_design_page_replaces_the_decisions_but_keeps_the_brief(): void
    {
        $this->artisan('design:generate', ['client' => 'Tidewater', 'product' => 'Berth scheduling for a port, used all shift.'])->assertSuccessful();

        Livewire::test('pages::designs.show', ['slug' => 'tidewater'])
            ->set('decisions.radius.value', 'round')
            ->call('regenerate')
            ->assertSet('decisions.radius.value', 'soft')
            ->assertSet('brief.client', 'Tidewater')
            ->assertSet('dirty', true);

        $this->assertSame(2, $this->source->calls);
    }

    #[Test]
    public function saving_from_the_design_page_keeps_when_the_model_chose(): void
    {
        $this->artisan('design:generate', ['client' => 'Tidewater', 'product' => 'Berth scheduling for a port, used all shift.'])->assertSuccessful();

        $generatedAt = Design::load('tidewater')->generatedAt;
        $this->assertNotNull($generatedAt);

        Livewire::test('pages::designs.show', ['slug' => 'tidewater'])
            ->set('decisions.radius.value', 'round')
            ->call('save')
            ->assertSet('dirty', false);

        $saved = Design::load('tidewater');
        $this->assertSame('round', $saved->decisions['radius']['value']);
        // A save is a person editing a decision, not the model choosing again.
        $this->assertSame($generatedAt, $saved->generatedAt);
        $this->assertSame('claude-test-model', $saved->generator['model']);
    }

    #[Test]
    public function the_new_design_page_renders(): void
    {
        $this->get('/designs/new')->assertOk()->assertSee('Describe the project');
    }
}
