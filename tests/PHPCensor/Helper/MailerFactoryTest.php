<?php

namespace tests\PHPCensor\Service;

use PHPCensor\Helper\MailerFactory;

/**
 * Unit tests for the ProjectService class.
 * @author Dan Cryer <dan@block8.co.uk>
 */
class MailerFactoryTest extends \PHPUnit\Framework\TestCase
{
   public function setUp()
    {
    }

    public function testExecute_TestGetMailConfig()
    {
        $config = [
            'smtp_address'           => 'mail.example.com',
            'smtp_port'              => 225,
            'smtp_encryption'        => 'tls',
            'smtp_username'          => 'php-censor-user',
            'smtp_password'          => 'php-censor-password',
            'default_mailto_address' => 'admin@php-censor.local',
        ];

        $factory = new MailerFactory(['email_settings' => $config]);

        $this->assertEquals($config['smtp_address'], $factory->getMailConfig('smtp_address'));
        $this->assertEquals($config['smtp_port'], $factory->getMailConfig('smtp_port'));
        $this->assertEquals($config['smtp_encryption'], $factory->getMailConfig('smtp_encryption'));
        $this->assertEquals($config['smtp_username'], $factory->getMailConfig('smtp_username'));
        $this->assertEquals($config['smtp_password'], $factory->getMailConfig('smtp_password'));
        $this->assertEquals($config['default_mailto_address'], $factory->getMailConfig('default_mailto_address'));
    }

    public function testExecute_TestMailer()
    {
        $config = [
            'smtp_address'           => 'mail.example.com',
            'smtp_port'              => 225,
            'smtp_encryption'        => 'tls',
            'smtp_username'          => 'php-censor-user',
            'smtp_password'          => 'php-censor-password',
            'default_mailto_address' => 'admin@php-censor.local',
        ];

        $factory = new MailerFactory(['email_settings' => $config]);
        $mailer = $factory->getSwiftMailerFromConfig();

        $this->assertEquals($config['smtp_address'], $mailer->getTransport()->getHost());
        $this->assertEquals($config['smtp_port'], $mailer->getTransport()->getPort());
        $this->assertEquals('tls', $mailer->getTransport()->getEncryption());
        $this->assertEquals($config['smtp_username'], $mailer->getTransport()->getUsername());
        $this->assertEquals($config['smtp_password'], $mailer->getTransport()->getPassword());
    }
}
