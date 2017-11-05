<?php

namespace Spatie\ResponseCache;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResponseSerializer
{
    const RESPONSE_TYPE_NORMAL = 'response_type_normal';
    const RESPONSE_TYPE_FILE = 'response_type_file';

    public function serialize(Response $response): string
    {
        return gzcompress(igbinary_serialize($this->getResponseData($response)), 1);
    }

    public function unserialize(string $serializedResponse): Response
    {
        $responseProperties = igbinary_unserialize(gzuncompress($serializedResponse));

        $response = $this->buildResponse($responseProperties);

        $response->headers = $responseProperties['headers'];

        return $response;
    }

    protected function getResponseData(Response $response): array
    {
        $statusCode = $response->getStatusCode();
        $headers = $response->headers;

        if ($response instanceof BinaryFileResponse) {
            $content = $response->getFile()->getPathname();
            $type = self::RESPONSE_TYPE_FILE;

            return compact('statusCode', 'headers', 'content', 'type');
        }

        $content = $response->getContent();
        $type = self::RESPONSE_TYPE_NORMAL;

        return compact('statusCode', 'headers', 'content', 'type');
    }

    protected function buildResponse(array $responseProperties): Response
    {
        $type = $responseProperties['type'] ?? self::RESPONSE_TYPE_NORMAL;

        if ($type === self::RESPONSE_TYPE_FILE) {
            return new BinaryFileResponse(
                $responseProperties['content'],
                $responseProperties['statusCode']
            );
        }

        return new Response($responseProperties['content'], $responseProperties['statusCode']);
    }
}
