<?php
namespace ICANID\SDK\API\Header;

class Telemetry extends Header
{

    /**
     * Telemetry constructor.
     *
     * @param string $telemetryData
     */
    public function __construct($telemetryData)
    {
        parent::__construct('ICANID-Client', $telemetryData);
    }
}
