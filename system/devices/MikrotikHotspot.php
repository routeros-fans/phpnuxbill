<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

use PEAR2\Net\RouterOS;

class MikrotikHotspot
{

    function connect_customer($customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        $mikrotik = $this->info($plan['routers']);
        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        Mikrotik::removeHotspotUser($client, $customer['username']);
        Mikrotik::removeHotspotActiveUser($client, $customer['username']);
        Mikrotik::addHotspotUser($client, $plan, $customer);
    }

    function disconnect_customer($customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        $mikrotik = $this->info($plan['routers']);
        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($p['pool_expired'])) {
            Mikrotik::setHotspotUserPackage($client, $customer['username'], 'EXPIRED NUXBILL ' . $p['pool_expired']);
        } else {
            Mikrotik::removeHotspotUser($client, $customer['username']);
        }
        Mikrotik::removeHotspotActiveUser($client, $customer['username']);
    }

    function change_customer($tur, $customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        $mikrotik = $this->info($plan['routers']);
        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        Mikrotik::removeHotspotUser($client, $customer['username']);
        Mikrotik::removeHotspotActiveUser($client, $customer['username']);
        Mikrotik::addHotspotUser($client, $plan, $customer);
    }

    function add_plan($plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        $mikrotik = $this->info($plan['routers']);
        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if ($plan['rate_down_unit'] == 'Kbps') {
            $unitdown = 'K';
        } else {
            $unitdown = 'M';
        }
        if ($plan['rate_up_unit'] == 'Kbps') {
            $unitup = 'K';
        } else {
            $unitup = 'M';
        }
        $rate = $plan['rate_up'] . $unitup . "/" . $plan['rate_down'] . $unitdown;
        $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $plan['name_plan'])
                ->setArgument('shared-users', $plan['shared_users'])
                ->setArgument('rate-limit', $rate)
        );
    }

    function update_plan($old_plan, $new_plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        $mikrotik = $this->info($new_plan['routers']);
        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if ($new_plan['rate_down_unit'] == 'Kbps') {
            $unitdown = 'K';
        } else {
            $unitdown = 'M';
        }
        if ($new_plan['rate_up_unit'] == 'Kbps') {
            $unitup = 'K';
        } else {
            $unitup = 'M';
        }
        $rate = $new_plan['rate_up'] . $unitup . "/" . $new_plan['rate_down'] . $unitdown;
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $old_plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            Mikrotik::addHotspotPlan($client, $new_plan['name_plan'], $new_plan['shared_users'], $rate);
        } else {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('name', $new_plan['name_plan'])
                    ->setArgument('shared-users', $new_plan['shared_users'])
                    ->setArgument('rate-limit', $rate)
            );
        }
    }

    function remove_plan($plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
        $mikrotik = $this->info($plan['routers']);
        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    function add_pool($pool){
        // Hotspot no need pool
    }

    function update_pool($old_pool, $new_pool){
        // Hotspot no need pool
    }

    function remove_pool($pool){
        // Hotspot no need pool
    }

    function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    function getClient($ip, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $iport = explode(":", $ip);
        return new RouterOS\Client($iport[0], $user, $pass, ($iport[1]) ? $iport[1] : null);
    }

    function isUserLogin($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('.id');
    }

    function logMeIn($client, $user, $pass, $ip, $mac)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/active/login');
        $client->sendSync(
            $addRequest
                ->setArgument('user', $user)
                ->setArgument('password', $pass)
                ->setArgument('ip', $ip)
                ->setArgument('mac-address', $mac)
        );
    }

    function logMeOut($client, $user)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $user)
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }

    function removeHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user print .proplist=.id',
            RouterOS\Query::where('name', $username)
        );
        $userID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $userID)
        );
    }

    function addHotspotUser($client, $plan, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
        if ($plan['typebp'] == "Limited") {
            if ($plan['limit_type'] == "Time_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-uptime', $timelimit)
                );
            } else if ($plan['limit_type'] == "Data_Limit") {
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            } else if ($plan['limit_type'] == "Both_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-uptime', $timelimit)
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            }
        } else {
            $client->sendSync(
                $addRequest
                    ->setArgument('name', $customer['username'])
                    ->setArgument('profile', $plan['name_plan'])
                    ->setArgument('comment', $customer['fullname'])
                    ->setArgument('email', $customer['email'])
                    ->setArgument('password', $customer['password'])
            );
        }
    }

    function setHotspotUser($client, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    function setHotspotUserPackage($client, $user, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan);
        $client->sendSync($setRequest);
    }

    function removeHotspotActiveUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('user', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    function sendSMS($client, $to, $message)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $smsRequest = new RouterOS\Request('/tool sms send');
        $smsRequest
            ->setArgument('phone-number', $to)
            ->setArgument('message', $message);
        $client->sendSync($smsRequest);
    }

    function getIpHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('address');
    }
}