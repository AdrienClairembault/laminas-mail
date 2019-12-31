<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mail\Header;

use Laminas\Mail\Address;
use Laminas\Mail\Header;

/**
 * @group      Laminas_Mail
 */
class SenderTest extends \PHPUnit_Framework_TestCase
{
    public function testFromStringCreatesValidReceivedHeader()
    {
        $sender = Header\Sender::fromString('Sender: <foo@bar>');
        $this->assertInstanceOf('Laminas\Mail\Header\HeaderInterface', $sender);
        $this->assertInstanceOf('Laminas\Mail\Header\Sender', $sender);
    }

    /**
     * @dataProvider validSenderDataProvider
     * @group ZF2015-04
     * @param string $email
     * @param null|string $name
     * @param string $expectedFieldValue,
     * @param string $encodedValue
     * @param string $encoding
     */
    public function testParseValidSenderHeader($email, $name, $expectedFieldValue, $encodedValue, $encoding)
    {
        $header = Header\Sender::fromString('Sender:' . $encodedValue);

        $this->assertEquals($expectedFieldValue, $header->getFieldValue());
        $this->assertEquals($encoding, $header->getEncoding());
    }

    public function testReceivedToStringReturnsHeaderFormattedString()
    {
        $sender = new Header\Sender();
        $sender->setAddress('foo@bar.com');

        $this->assertEquals('Sender: <foo@bar.com>', $sender->toString());
    }

    /**
     * @dataProvider invalidSenderEncodedDataProvider
     * @group ZF2015-04
     * @param string $decodedValue
     * @param string $expectedException
     * @param string|null $expectedExceptionMessage
     */
    public function testParseInvalidSenderHeaderThrowException(
        $decodedValue,
        $expectedException,
        $expectedExceptionMessage
    ) {
        $this->setExpectedException($expectedException, $expectedExceptionMessage);
        Header\Sender::fromString('Sender:' . $decodedValue);
    }

    /**
     * @dataProvider validSenderDataProvider
     * @group ZF2015-04
     * @param string $email
     * @param null|string $name
     * @param string $encodedValue
     * @param string $expectedFieldValue,
     * @param string $encoding
     */
    public function testSetAddressValidValue($email, $name, $expectedFieldValue, $encodedValue, $encoding)
    {
        $header = new Header\Sender();
        $header->setAddress($email, $name);

        $this->assertEquals($expectedFieldValue, $header->getFieldValue());
        $this->assertEquals('Sender: ' . $encodedValue, $header->toString());
        $this->assertEquals($encoding, $header->getEncoding());
    }

    /**
     * @dataProvider invalidSenderDataProvider
     * @group ZF2015-04
     * @param string $email
     * @param null|string $name
     */
    public function testSetAddressInvalidValue($email, $name)
    {
        $header = new Header\Sender();
        $this->setExpectedException('Laminas\Mail\Exception\InvalidArgumentException');
        $header->setAddress($email, $name);
    }

    /**
     * @dataProvider validSenderDataProvider
     * @group ZF2015-04
     * @param string $email
     * @param null|string $name
     * @param string $expectedFieldValue,
     * @param string $encodedValue
     * @param string $encoding
     */
    public function testSetAddressValidAddressObject($email, $name, $expectedFieldValue, $encodedValue, $encoding)
    {
        $address = new Address($email, $name);

        $header = new Header\Sender();
        $header->setAddress($address);

        $this->assertSame($address, $header->getAddress());
        $this->assertEquals($expectedFieldValue, $header->getFieldValue());
        $this->assertEquals('Sender: ' . $encodedValue, $header->toString());
        $this->assertEquals($encoding, $header->getEncoding());
    }

    public function validSenderDataProvider()
    {
        return array(
            // Description => [sender address, sender name, getFieldValue, encoded version, encoding],
            'ASCII address' => array(
                'foo@bar',
                null,
                '<foo@bar>',
                '<foo@bar>',
                'ASCII'
            ),
            'ASCII name' => array(
                'foo@bar',
                'foo',
                'foo <foo@bar>',
                'foo <foo@bar>',
                'ASCII'
            ),
            'UTF-8 name' => array(
                'foo@bar',
                'ázÁZ09',
                'ázÁZ09 <foo@bar>',
                '=?UTF-8?Q?=C3=A1z=C3=81Z09?= <foo@bar>',
                'UTF-8'
            ),
        );
    }

    public function invalidSenderDataProvider()
    {
        $mailInvalidArgumentException = 'Laminas\Mail\Exception\InvalidArgumentException';

        return array(
            // Description => [sender address, sender name, exception class, exception message],
            'Empty' => array('', null, $mailInvalidArgumentException, null),
            'any ASCII' => array('azAZ09-_', null, $mailInvalidArgumentException, null),
            'any UTF-8' => array('ázÁZ09-_', null, $mailInvalidArgumentException, null),

            // CRLF @group ZF2015-04 cases
            array("foo@bar\n", null, $mailInvalidArgumentException, null),
            array("foo@bar\r", null, $mailInvalidArgumentException, null),
            array("foo@bar\r\n", null, $mailInvalidArgumentException, null),
            array("foo@bar", "\r", $mailInvalidArgumentException, null),
            array("foo@bar", "\n", $mailInvalidArgumentException, null),
            array("foo@bar", "\r\n", $mailInvalidArgumentException, null),
            array("foo@bar", "foo\r\nevilBody", $mailInvalidArgumentException, null),
            array("foo@bar", "\r\nevilBody", $mailInvalidArgumentException, null),
        );
    }

    public function invalidSenderEncodedDataProvider()
    {
        $mailInvalidArgumentException = 'Laminas\Mail\Exception\InvalidArgumentException';
        $headerInvalidArgumentException = 'Laminas\Mail\Header\Exception\InvalidArgumentException';

        return array(
            // Description => [decoded format, exception class, exception message],
            'Empty' => array('', $mailInvalidArgumentException, null),
            'any ASCII' => array('azAZ09-_', $mailInvalidArgumentException, null),
            'any UTF-8' => array('ázÁZ09-_', $mailInvalidArgumentException, null),
            array("xxx yyy\n", $mailInvalidArgumentException, null),
            array("xxx yyy\r\n", $mailInvalidArgumentException, null),
            array("xxx yyy\r\n\r\n", $mailInvalidArgumentException, null),
            array("xxx\r\ny\r\nyy", $mailInvalidArgumentException, null),
            array("foo\r\n@\r\nbar", $mailInvalidArgumentException, null),

            array("ázÁZ09 <foo@bar>", $headerInvalidArgumentException, null),
            'newline' => array("<foo@bar>\n", $headerInvalidArgumentException, null),
            'cr-lf' => array("<foo@bar>\r\n", $headerInvalidArgumentException, null),
            'cr-lf-wsp' => array("<foo@bar>\r\n\r\n", $headerInvalidArgumentException, null),
            'multiline' => array("<foo\r\n@\r\nbar>", $headerInvalidArgumentException, null),
        );
    }
}
