<?php

namespace Tests\V2\Image;

use App\Models\V2\Image;
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
            ->seeJson([
                'id' => 'img-test',
                'name' => 'Test Image',
                'vpc_id' => null,
                'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                'description' => 'CentOS (Community enterprise Operating System)',
                'platform' => 'Linux',
                'public' => true,
            ])
            ->dontSeeJson([
                'script_template' => '',
                'vm_template' => 'CentOS7 x86_64',
                'active' => true,
            ])
            ->assertResponseStatus(200);
    }

    public function testIndexPublicImageAdmin()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/images')
            ->seeJson([
                'id' => 'img-test',
                'name' => 'Test Image',
                'vpc_id' => null,
                'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                'description' => 'CentOS (Community enterprise Operating System)',
                'platform' => 'Linux',
                'public' => true,
                'script_template' => '',
                'vm_template' => 'CentOS7 x86_64',
                'active' => true,
            ])
            ->assertResponseStatus(200);
    }

    public function testIndexPrivateImageNotAdminNotOwner()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));

        factory(Image::class)->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images')
            ->dontSeeJson([
                'img-private-test'
            ])
            ->assertResponseStatus(200);
    }

    public function testIndexPrivateImageNotAdminIsOwner()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        factory(Image::class)->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images')
            ->seeJson([
                'img-private-test'
            ])
            ->assertResponseStatus(200);
    }

    public function testIndexPrivateImageIsAdminNotOwner()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        factory(Image::class)->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images')
            ->seeJson([
                'img-private-test'
            ])
            ->assertResponseStatus(200);
    }

    public function testShowPublicImageNotAdmin()
    {
        $this->get('/v2/images/' . $this->image()->id)
            ->seeJson([
                'id' => 'img-test',
                'name' => 'Test Image',
                'vpc_id' => null,
                'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                'description' => 'CentOS (Community enterprise Operating System)',
                'platform' => 'Linux',
                'public' => true,
            ])
            ->dontSeeJson([
                'script_template' => '',
                'vm_template' => 'CentOS7 x86_64',
                'active' => true,
            ])
            ->assertResponseStatus(200);
    }

    public function testShowPublicImageAdmin()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/images/' . $this->image()->id)
            ->seeJson([
                'id' => 'img-test',
                'name' => 'Test Image',
                'vpc_id' => null,
                'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                'description' => 'CentOS (Community enterprise Operating System)',
                'platform' => 'Linux',
                'public' => true,
                'script_template' => '',
                'vm_template' => 'CentOS7 x86_64',
                'active' => true,
            ])
            ->assertResponseStatus(200);
    }

    public function testShowPrivateImageNotAdminNotOwner()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));

        factory(Image::class)->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images/img-private-test')->assertResponseStatus(404);
    }

    public function testShowPrivateImageNotAdminIsOwner()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        factory(Image::class)->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images/img-private-test')
            ->seeJson([
                'img-private-test'
            ])
            ->assertResponseStatus(200);
    }

    public function testShowPrivateImageIsAdminNotOwner()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        factory(Image::class)->create([
            'id' => 'img-private-test',
            'vpc_id' => $this->vpc()->id,
            'public' => false,
        ]);

        $this->get('/v2/images/img-private-test')
            ->seeJson([
                'img-private-test'
            ])
            ->assertResponseStatus(200);
    }
}
