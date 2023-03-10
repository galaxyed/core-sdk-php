<?php

namespace ICANID\SDK\API\Management;

class Stats extends GenericResource
{
    /**
     *
     * @return mixed
     */
    public function getActiveUsersCount()
    {
        return $this->apiClient->method('get')
        ->addPath('stats', 'active-users')
        ->call();
    }

    /**
     *
     * @param  string $from
     * @param  string $to
     * @return mixed
     */
    public function getDailyStats($from, $to)
    {
        return $this->apiClient->method('get')
        ->addPath('stats', 'daily')
        ->withParam('from', $from)
        ->withParam('to', $to)
        ->call();
    }
}
