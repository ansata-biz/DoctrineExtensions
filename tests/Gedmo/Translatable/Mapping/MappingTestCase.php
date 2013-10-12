<?php

namespace Gedmo\Translatable\Mapping;

use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Translatable\Mapping\TranslatableMetadata;

class MappingTestCase extends ObjectManagerTestCase
{
    protected function assertMapping(TranslatableMetadata $exm)
    {
        $this->assertFalse($exm->isEmpty());
        $this->assertCount(3, $fields = $exm->getFields());

        $this->assertContains('title', $fields);
        $this->assertContains('content', $fields);
        $this->assertContains('author', $fields);

        $this->assertSame('Gedmo\Fixture\Unmapped\TranslatableTranslation', $exm->getTranslationClass());
    }
}

