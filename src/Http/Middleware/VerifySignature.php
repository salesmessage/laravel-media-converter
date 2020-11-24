<?php

namespace Meema\MediaConvert\Http\Middleware;

use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Closure;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VerifySignature
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // Create a message from the post data and validate its signature
            $message = Message::fromRawPostData();

            // Validate the message
            $validator = new MessageValidator();

            if ($validator->isValid($message)) {
                return $next($request);
            }

            throw new NotFoundHttpException();
        } catch (InvalidSnsMessageException $e) {
            throw new NotFoundHttpException();
        }
    }
}
