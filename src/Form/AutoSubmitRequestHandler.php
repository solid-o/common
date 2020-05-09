<?php

declare(strict_types=1);

namespace Solido\Common\Form;

use Solido\BodyConverter\BodyConverter;
use Solido\BodyConverter\BodyConverterInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use function call_user_func;
use function class_exists;
use function is_array;
use function Safe\array_replace_recursive;

/**
 * @internal
 */
final class AutoSubmitRequestHandler implements RequestHandlerInterface
{
    private ServerParams $serverParams;
    private ?BodyConverterInterface $bodyConverter;

    public function __construct(?ServerParams $serverParams = null, ?BodyConverterInterface $bodyConverter = null)
    {
        if ($bodyConverter === null && class_exists(BodyConverter::class)) {
            $bodyConverter = new BodyConverter();
        }

        $this->serverParams = $serverParams ?? new ServerParams();
        $this->bodyConverter = $bodyConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(FormInterface $form, $request = null): void
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        } elseif (! $request instanceof Request) {
            throw new UnexpectedTypeException($request, Request::class);
        }

        $name = $form->getName();
        $method = $form->getConfig()->getMethod();

        if ($request->getMethod() !== $method) {
            return;
        }

        // For request methods that must not have a request body we fetch data
        // from the query string. Otherwise we look for data in the request body.
        if ($method === Request::METHOD_GET || $method === Request::METHOD_HEAD || $method === Request::METHOD_TRACE) {
            if ($name === '') {
                $data = $request->query->all();
            } else {
                // Don't submit GET requests if the form's name does not exist
                // in the request
                if (! $request->query->has($name)) {
                    return;
                }

                $data = $request->query->get($name);
            }
        } else {
            // Mark the form with an error if the uploaded size was too large
            // This is done here and not in FormValidator because $_POST is
            // empty when that error occurs. Hence the form is never submitted.
            if ($this->serverParams->hasPostMaxSizeBeenExceeded()) {
                // Submit the form, but don't clear the default values
                $form->submit(null, false);

                $form->addError(new FormError(
                    call_user_func($form->getConfig()->getOption('upload_max_size_message')),
                    null,
                    ['{{ max }}' => $this->serverParams->getNormalizedIniPostMaxSize()]
                ));

                return;
            }

            $parameterBag = $this->bodyConverter !== null ? $this->bodyConverter->decode($request) : $request->request;
            if ($name === '') {
                $params = $parameterBag->all();
                $files = $request->files->all();
            } elseif ($request->request->has($name) || $request->files->has($name)) {
                $default = $form->getConfig()->getCompound() ? [] : null;
                $params = $parameterBag->get($name, $default);
                $files = $request->files->get($name, $default);
            } else {
                // Don't submit the form if it is not present in the request
                return;
            }

            if (is_array($params) && is_array($files)) {
                $data = array_replace_recursive($params, $files);
            } else {
                $data = $params ?? $files;
            }
        }

        $form->submit($data, $method !== Request::METHOD_PATCH);
    }

    /**
     * {@inheritdoc}
     */
    public function isFileUpload($data): bool
    {
        return $data instanceof File;
    }

    /**
     * Gets the upload file error from data.
     *
     * @param mixed $data
     */
    public function getUploadFileError($data): ?int
    {
        if (! $data instanceof UploadedFile || $data->isValid()) {
            return null;
        }

        return $data->getError();
    }
}
