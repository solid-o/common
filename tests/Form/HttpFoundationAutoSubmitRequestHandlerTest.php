<?php

declare(strict_types=1);

namespace Solido\Common\Tests\Form;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Solido\BodyConverter\BodyConverterInterface;
use Solido\Common\AdapterFactory;
use Solido\Common\Form\AutoSubmitRequestHandler;
use Solido\Common\RequestAdapter\SymfonyHttpFoundationRequestAdapter;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class HttpFoundationAutoSubmitRequestHandlerTest extends HttpFoundationRequestHandlerTest
{
    use ProphecyTrait;

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

    public function testRequestHandlerShouldUseTheInjectedBodyConverter(): void
    {
        $converter = $this->prophesize(BodyConverterInterface::class);
        $converter->decode(Argument::any())
            ->shouldBeCalled()
            ->willReturn(['param1' => 'DATA']);

        $handler = new AutoSubmitRequestHandler($this->serverParams, null, $converter->reveal());
        $form = $this->createForm('', 'POST', true);
        $form->add($this->createForm('param1'));

        $request = new Request([], [], [], [], [], [], '{}');
        $request->setMethod(Request::METHOD_POST);

        $handler->handleRequest($form, $request);

        self::assertTrue($form->isSubmitted());
        self::assertEquals('DATA', $form->get('param1')->getViewData());
    }

    public function testHandlerShouldUseTheInjectedAdapterFactory(): void
    {
        $factory = $this->prophesize(AdapterFactory::class);
        $factory->createRequestAdapter(Argument::any())
            ->shouldBeCalled()
            ->will(function ($args) {
                return new SymfonyHttpFoundationRequestAdapter($args[0]);
            });

        $handler = new AutoSubmitRequestHandler($this->serverParams, $factory->reveal());
        $form = $this->createForm('', 'POST', true);
        $form->add($this->createForm('param1'));

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"param1": "DATA"}');
        $request->setMethod(Request::METHOD_POST);

        $handler->handleRequest($form, $request);

        self::assertTrue($form->isSubmitted());
        self::assertEquals('DATA', $form->get('param1')->getViewData());
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
