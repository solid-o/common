<?php

declare(strict_types=1);

namespace Solido\Common\Tests\Form;

use Solido\Common\Form\AutoSubmitRequestHandler;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class AutoSubmitRequestHandlerTest extends HttpFoundationRequestHandlerTest
{
    private static array $serverBackup;

    public static function setUpBeforeClass(): void
    {
        self::$serverBackup = $_SERVER;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = [
            // PHPUnit needs this entry
            'SCRIPT_NAME' => self::$serverBackup['SCRIPT_NAME'],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = self::$serverBackup;
    }

    protected function getRequestHandler(): RequestHandlerInterface
    {
        return new AutoSubmitRequestHandler($this->serverParams);
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDoNotSubmitFormWithEmptyNameIfNoFieldInRequest($method): void
    {
        self::markTestSkipped('Not applicable to this request handler');
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDoSubmitFormWithEmptyNameIfNoFieldInRequest(string $method): void
    {
        $form = $this->createForm('', $method, true);
        $form->add($this->createForm('param1'));
        $form->add($this->createForm('param2'));

        $this->setRequestData($method, ['paramx' => 'submitted value']);

        $this->requestHandler->handleRequest($form, $this->request);

        self::assertTrue($form->isSubmitted());
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDoSubmitFormWithNullRequest(string $method): void
    {
        $form = $this->createForm('', $method, true);
        $form->add($this->createForm('param1'));
        $form->add($this->createForm('param2'));

        $_SERVER['REQUEST_METHOD'] = $method;
        $_POST = ['paramx' => 'submitted value'];

        $this->requestHandler->handleRequest($form, null);

        self::assertTrue($form->isSubmitted());
    }

    /**
     * @dataProvider methodExceptGetProvider
     */
    public function testShouldConvertRequestContent(string $method): void
    {
        $form = $this->createForm('', $method, true);
        $form->add($this->createForm('param1'));
        $form->add($this->createForm('param2'));

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], '{ "param1": "value" }');
        $request->setMethod($method);

        $this->requestHandler->handleRequest($form, $request);

        self::assertTrue($form->isSubmitted());
        self::assertEquals('value', $form->get('param1')->getViewData());
    }
}
