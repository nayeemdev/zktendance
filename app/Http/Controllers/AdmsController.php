<?php

namespace App\Http\Controllers;

use App\Services\AdmsService;
use Illuminate\Http\Request;

class AdmsController extends Controller
{
    public function __construct(private AdmsService $adms) {}

    public function handshake(Request $request)
    {
        $device = $this->adms->findDevice($request->query('SN'));

        return $this->text($device ? $this->adms->handshake($device) : 'OK');
    }

    public function receive(Request $request)
    {
        $device = $this->adms->findDevice($request->query('SN'));
        if (! $device) {
            return $this->text('OK');
        }

        if (strtoupper((string) $request->query('table')) === 'ATTLOG') {
            return $this->text('OK: '.$this->adms->receiveAttendance($device, $request->getContent()));
        }

        return $this->text('OK');
    }

    public function commands(Request $request)
    {
        $device = $this->adms->findDevice($request->query('SN'));

        return $this->text($device ? $this->adms->pendingCommands($device) : 'OK');
    }

    public function commandResult(Request $request)
    {
        $device = $this->adms->findDevice($request->query('SN'));
        if ($device) {
            $this->adms->commandResult($device, $request->getContent());
        }

        return $this->text('OK');
    }

    private function text(string $body)
    {
        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
