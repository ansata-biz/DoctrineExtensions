<?php

namespace Mapping;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\EncoderExtension\EncoderListener;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Fixture\Unmapped\Person;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

class EncoderExtensionWithEntityManagerTest extends ObjectManagerTestCase
{
    const PERSON = 'Fixture\Unmapped\Person';

    private $em;
    private $encoder;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->encoder = new EncoderListener);

        $config = $this->getEntityManagerConfiguration();
        $config->setMetadataDriverImpl(new EncoderExtensionWithEntityManagerTestDriver);

        $this->em = $this->createEntityManager($evm, null, $config);
        $this->createSchema($this->em, array(
            self::PERSON
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldLoadEncoderExtensionMetadata()
    {
        $meta = $this->em->getClassMetadata(self::PERSON);
        $config = $this->encoder->getConfiguration($this->em, self::PERSON);
        $this->assertArrayHasKey('encode', $config);
        $this->assertCount(1, $config['encode']);

        $this->assertArrayHasKey('password', $config['encode']);
        $options = $config['encode']['password'];
        $this->assertEquals('sha1', $options['type']);
        $this->assertEquals('guess', $options['secret']);
    }

    /**
     * @test
     */
    function shouldHaveEncodedPassword()
    {
        $user = new Person;
        $user->setName('encode me');
        $user->setPassword('secret');
        $this->em->persist($user);
        $this->em->flush();

        $this->assertSame(sha1('guess' . 'secret'), $user->getPassword());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::PERSON
        );
    }
}

class EncoderExtensionWithEntityManagerTestDriver implements MappingDriver
{
    public function getAllClassNames()
    {
        return array('Fixture\Unmapped\Person', 'Fixture\Unmapped\Nameable');
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if ($className === 'Fixture\Unmapped\Person') {
            $id = array();
            $id['fieldName'] = 'id';
            $id['type'] = 'integer';
            $id['nullable'] = false;
            $id['columnName'] = 'id';
            $id['id'] = true;

            $metadata->setIdGeneratorType(
                constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_AUTO')
            );

            $metadata->mapField($id);

            $password = array(
                'fieldName' => 'password',
                'type' => 'string',
                'nullable' => false,
                'length' => 32,
                'columnName' => 'password'
            );

            $metadata->mapField($password);
        } elseif ($className === 'Fixture\Unmapped\Nameable') {
            $metadata->isMappedSuperclass = true;

            $name = array();
            $name['fieldName'] = 'name';
            $name['type'] = 'string';
            $name['length'] = 32;
            $name['nullable'] = false;
            $name['columnName'] = 'name';

            $metadata->mapField($name);
        }
    }

    public function isTransient($className)
    {
        return !in_array($className, $this->getAllClassNames());
    }
}
