<?php

namespace Tests\V2\Image;

use App\Models\V2\Image;
use Database\Seeders\SoftwareSeeder;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->image();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testIndexPublicImageNotAdmin()
    {
        $this->get('/v2/images')
            ->assertJsonFragment([
                'id' => 'img-test',
                'name' => 'Test Image',
                'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                'description' => 'CentOS (Community enterprise Operating System)',
                'platform' => 'Linux',
            ])
            ->assertJsonMissing([
                'vpc_id' => null,
                'script_template' => '',
                'vm_template' => 'CentOS7 x86_64',
                'active' => true,
            ])
            ->assertStatus(200);
    }

    public function testIndexPublicImageAdmin()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/images')
            ->assertJsonFragment([
                'id' => 'img-test',
                'name' => 'Test Image',
                'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                'description' => 'CentOS (Community enterprise Operating System)',
                'platform' => 'Linux',
                'public' => true,
                'script_template' => '',
                'vm_template' => 'CentOS7 x86_64',
                'active' => true,
            ])
            ->assertStatus(200);
    }

    public function testIndexPrivateImageNotAdminNotOwner()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));

        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images')
            ->assertJsonMissing([
                'img-private-test'
            ])
            ->assertStatus(200);
    }

    public function testIndexPrivateImageNotAdminIsOwner()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'visibility' => Image::VISIBILITY_PRIVATE,
        ]);

        $this->get('/v2/images')
            ->assertJsonFragment([
                'img-private-test'
            ])
            ->assertStatus(200);
    }

    public function testIndexPrivateImageIsAdminNotOwner()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images')
            ->assertJsonFragment([
                'img-private-test'
            ])
            ->assertStatus(200);
    }

    public function testShowPublicImageNotAdmin()
    {
        $this->get('/v2/images/' . $this->image()->id)
            ->assertJsonFragment([
                'id' => 'img-test',
                'name' => 'Test Image',
                'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                'description' => 'CentOS (Community enterprise Operating System)',
                'platform' => 'Linux',
                'visibility' => Image::VISIBILITY_PUBLIC,
            ])
            ->assertJsonMissing([
                'script_template' => '',
                'vm_template' => 'CentOS7 x86_64',
                'active' => true,
                'public' => true,
            ])
            ->assertStatus(200);
    }

    public function testShowPublicImageAdmin()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/images/' . $this->image()->id)
            ->assertJsonFragment([
                'id' => 'img-test',
                'name' => 'Test Image',
                'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                'description' => 'CentOS (Community enterprise Operating System)',
                'platform' => 'Linux',
                'public' => true,
                'script_template' => '',
                'vm_template' => 'CentOS7 x86_64',
                'active' => true,
            ])
            ->assertStatus(200);
    }

    public function testShowPrivateImageNotAdminNotOwner()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));

        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images/img-private-test')->assertStatus(404);
    }

    public function testShowPrivateImageNotAdminIsOwner()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'visibility' => Image::VISIBILITY_PRIVATE,
        ]);

        $this->get('/v2/images/img-private-test')
            ->assertJsonFragment([
                'img-private-test'
            ])
            ->assertStatus(200);
    }

    public function testShowPrivateImageIsAdminNotOwner()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        Image::factory()->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'visibility' => Image::VISIBILITY_PRIVATE,
        ]);

        $this->get('/v2/images/img-private-test')
            ->assertJsonFragment([
                'img-private-test',
                'vpc_id' => $this->vpc()->id,
            ])
            ->assertStatus(200);
    }

    public function testImageSoftware()
    {
        (new SoftwareSeeder())->run();

        $this->image()->software()->sync(['soft-aaaaaaaa']);
        
        $this->get('/v2/images/' . $this->image()->id . '/software')
            ->assertJsonFragment([
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'platform' => 'Linux',
            ])
            ->assertStatus(200);
    }

    public function testHiddenImageParamAdmn()
    {
        $this->imageParameter()->setAttribute('is_hidden', true)->save();

        $this->get('/v2/images/' . $this->image()->id . '/parameters')
            ->assertJsonFragment([
                'id' => 'iparam-test',
            ])
            ->assertStatus(200);
    }

    public function testHiddenImageParamNotAdmn()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->imageParameter()->setAttribute('is_hidden', true)->save();

        $this->get('/v2/images/' . $this->image()->id . '/parameters')
            ->assertJsonMissing([
                'id' => 'iparam-test',
            ])
            ->assertStatus(200);
    }
}
