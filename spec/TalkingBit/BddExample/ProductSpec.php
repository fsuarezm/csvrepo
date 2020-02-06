<?php

namespace spec\TalkingBit\BddExample;

use PhpSpec\ObjectBehavior;
use TalkingBit\BddExample\Product;

class ProductSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Product::class);
    }
}
