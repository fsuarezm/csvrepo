<?php

namespace spec\TalkingBit\BddExample\VO;

use PhpSpec\ObjectBehavior;
use TalkingBit\BddExample\VO\FilePath;

class FilePathSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(FilePath::class);
    }
}
