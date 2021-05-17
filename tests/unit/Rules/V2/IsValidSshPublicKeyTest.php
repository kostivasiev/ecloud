<?php


namespace Rules\V2;


use App\Rules\V2\IsValidSshPublicKey;
use Tests\TestCase;

class IsValidSshPublicKeyTest extends TestCase
{
    public function testInvalidPublicKeyFails() {
        $rule = new IsValidSshPublicKey();

        $result = $rule->passes('public_key', "invalid");

        $this->assertFalse($result );
    }

    public function testValidRSAPublicKeyPasses() {
        $rule = new IsValidSshPublicKey();

        $result = $rule->passes('public_key', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==');

        $this->assertTrue($result );
    }

    public function testValidED25519PublicKeyPasses() {
        $rule = new IsValidSshPublicKey();

        $result = $rule->passes('public_key', 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIGWlOgAlyZGy1eoRkswV32LnC47io1h0ccfNJHNsPBdQ');

        $this->assertTrue($result );
    }

    public function testValidPublicKeyWithCommentPasses() {
        $rule = new IsValidSshPublicKey();

        $result = $rule->passes('public_key', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ== test-key');

        $this->assertTrue($result );
    }
}