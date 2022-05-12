<?php

namespace Meema\MediaConverter\Http\Controllers;

use Aws\Sns\Message;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Meema\MediaConverter\Events\ConversionHasCompleted;
use Meema\MediaConverter\Events\ConversionHasError;
use Meema\MediaConverter\Events\ConversionHasInputInformation;
use Meema\MediaConverter\Events\ConversionHasNewWarning;
use Meema\MediaConverter\Events\ConversionHasStatusUpdate;
use Meema\MediaConverter\Events\ConversionIsProgressing;
use Meema\MediaConverter\Events\ConversionQueueHop;

class IncomingWebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('verify-signature');
    }

    /**
     * @throws \Exception
     */
    public function __invoke(): Response
    {
        try {
            $message = $this->ensureSubscriptionIsConfirmed();

            Log::info('incoming MediaConvert webhook message', $message);

            if (! array_key_exists('detail', $message)) {
                Log::alert('incoming MediaConvert webhook: "detail"-key does not exist');

                return $this->response();
            }

            $detail = $message['detail'];

            if (! array_key_exists('status', $detail)) {
                Log::alert('incoming MediaConvert webhook: "status"-key does not exist');

                return $this->response();
            }

            $status = $detail['status'];

            $this->fireEventFor($status, $message);
        } catch (\Exception $e) {
            Log::error($e);
        }

        return $this->response();
    }

    protected function response(): Response
    {
        return new Response(null, Response::HTTP_OK);
    }

    /**
     * @param $status
     * @param $message
     *
     * @throws \Exception
     */
    public function fireEventFor($status, $message): void
    {
        switch ($status) {
            case 'PROGRESSING':
                event(new ConversionIsProgressing($message));
                break;
            case 'INPUT_INFORMATION':
                event(new ConversionHasInputInformation($message));
                break;
            case 'COMPLETE':
                event(new ConversionHasCompleted($message));
                break;
            case 'STATUS_UPDATE':
                event(new ConversionHasStatusUpdate($message));
                break;
            case 'NEW_WARNING':
                event(new ConversionHasNewWarning($message));
                break;
            case 'QUEUE_HOP':
                event(new ConversionQueueHop($message));
                break;
            case 'ERROR':
                event(new ConversionHasError($message));
                break;
            default:
                throw new \Exception();
        }
    }

    /**
     * @return array
     */
    public function ensureSubscriptionIsConfirmed(): array
    {
        $message = Message::fromRawPostData()->toArray();

        if (array_key_exists('SubscribeURL', $message)) {

            (new Client())->get($message['SubscribeURL']);
        }

        return json_decode($message['Message'], true);
    }
}
