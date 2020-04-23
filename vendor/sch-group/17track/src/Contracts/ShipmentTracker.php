<?php

namespace SchGroup\SeventeenTrack\Contracts;

use SchGroup\SeventeenTrack\Components\TrackEvent;

interface ShipmentTracker
{
    public function register(string $trackNumber,string $carrier);

    public function getTrackInfo(string $trackNumber, int $carrier = null);

    public function getPureTrackInfo(string $trackNumber, int $carrier = null);

    public function getLastTrackEvent(string $trackNumber, int $carrier = null);

    public function getLastTrackEventMulti(array $trackNumbers);

    public function changeCarrier(string $trackNumber, int $carrierNew, int $carrierOld = null);

    public function stopTracking(string $trackNumber, int $carrier = null);

    public function reTrack(string $trackNumber, int $carrier = null);

    public function getTrackInfoMulti(array $trackNumbers);

    public function registerMulti(array $trackNumbers);

    public function stopTrackingMulti(array $trackNumbers);

    public function changeCarrierMulti(array $trackNumbers);

    public function reTrackMulti(array $trackNumbers);

}