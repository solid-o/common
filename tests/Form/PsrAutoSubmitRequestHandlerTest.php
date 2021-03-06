<?php

declare(strict_types=1);

namespace Solido\Common\Tests\Form;

use Solido\Common\Form\AutoSubmitRequestHandler;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class PsrAutoSubmitRequestHandlerTest extends PsrServerRequestHandlerTest
{
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
}
