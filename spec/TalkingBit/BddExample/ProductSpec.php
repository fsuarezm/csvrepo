<?php

namespace spec\TalkingBit\BddExample;

use PhpSpec\ObjectBehavior;
use TalkingBit\BddExample\Product;

class ProductSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith(101, 'Product 1', 10.25);
        $this->shouldHaveType(Product::class);
    }
}
