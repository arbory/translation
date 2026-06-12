<?php namespace Waavi\Translation\Test\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Store;
use Waavi\Translation\Cache\SimpleRepository;
use Waavi\Translation\Test\TestCase;
use \Mockery;

class SimpleRepositoryTest extends TestCase
{
    public function setUp(): void
    {
        // During the parent's setup, both a 'es' 'Spanish' and 'en' 'English' languages are inserted into the database.
        parent::setUp();
        $this->store = new ArrayStore;
        $this->repo  = new SimpleRepository($this->store, 'translation');
    }

    public function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_has_with_no_entry()
    {
        $this->assertFalse($this->repo->has('en', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_has_returns_true_if_entry()
    {
        $this->repo->put('en', 'namespace', 'group', 'key', 60);
        $this->assertTrue($this->repo->has('en', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_get_returns_null_if_empty()
    {
        $this->assertNull($this->repo->get('en', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_get_return_content_if_hit()
    {
        $this->repo->put('en', 'namespace', 'group', 'value', 60);
        $this->assertEquals('value', $this->repo->get('en', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_flush_removes_only_the_given_entry()
    {
        $this->repo->put('en', 'namespace', 'group', 'value', 60);
        $this->repo->put('es', 'namespace', 'group', 'valor', 60);
        $this->repo->flush('en', 'namespace', 'group');
        $this->assertNull($this->repo->get('en', 'namespace', 'group'));
        $this->assertEquals('valor', $this->repo->get('es', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_flush_all_removes_all_translation_entries()
    {
        $this->repo->put('en', 'namespace', 'group', 'value', 60);
        $this->repo->put('es', 'namespace', 'group', 'valor', 60);
        $this->repo->flushAll();
        $this->assertNull($this->repo->get('en', 'namespace', 'group'));
        $this->assertNull($this->repo->get('es', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_flush_all_keeps_other_cache_entries()
    {
        $this->store->put('unrelated', 'app value', 60);
        $this->repo->put('en', 'namespace', 'group', 'value', 60);
        $this->repo->flushAll();
        $this->assertEquals('app value', $this->store->get('unrelated'));
        $this->assertNull($this->repo->get('en', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_put_works_after_flush_all()
    {
        $this->repo->put('en', 'namespace', 'group', 'old value', 60);
        $this->repo->flushAll();
        $this->repo->put('en', 'namespace', 'group', 'new value', 60);
        $this->assertEquals('new value', $this->repo->get('en', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_consecutive_flush_all_calls_keep_invalidating()
    {
        $this->repo->put('en', 'namespace', 'group', 'first', 60);
        $this->repo->flushAll();
        $this->repo->put('en', 'namespace', 'group', 'second', 60);
        $this->repo->flushAll();
        $this->assertNull($this->repo->get('en', 'namespace', 'group'));
    }

    /**
     * @test
     */
    public function test_put_passes_the_timeout_to_the_store_in_seconds()
    {
        $store = Mockery::mock(Store::class);
        $store->shouldReceive('get')->andReturn(null);
        $store->shouldReceive('put')->with(Mockery::type('string'), 'value', 3600)->once();
        $repo = new SimpleRepository($store, 'translation');
        $repo->put('en', 'namespace', 'group', 'value', 60);
    }
}
