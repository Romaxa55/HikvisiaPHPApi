#!/usr/bin/php -q
<?php

error_reporting(0);


class hik
{

    private $version = "1.1";
    private $home_dir = '/home/ts/connect/connect.conf';
    private $xml; // Временные объект xml
    private $statusHardware;
    private $error;
    private $apiUrl;
    private $videoParamets;
    private $videoParamets2;
    private $red = "\033[0;31m";
    private $green = "\033[0;32m";
    private $nc = "\033[0m";
    public $ip; //host
    public $user; //log
    public $password; //pass

    public function logo()
    {
        system("clear");
        print "\033[0;34m
    ''~``                                             ''~``
                            ( o o )
    +------------------.oooO--(_)--Oooo.------------------+
    |           Hikvision Script Version {$this->version}              |
    |                    .oooO                            |
    |                    (   )   Oooo.                    |
    +---------------------\ (----(   )--------------------+
                           \_)    ) /
                                 (_/
    \033[0m";
    }

    private function DestroyAll()
    {
        unset($this->xml); // Временные объект xml
        unset($this->statusHardware);
//        unset($this->error);
        unset($this->apiUrl);
        unset($this->ip); //host
        unset($this->user); //log
        unset($this->password); //pass
    }

    public function setVideoConfig()
    {  // подключаемся к камере
        if (is_string($this->ip)) { //Если ip один
            $this->setUser();

            while (true) {
                if (!$this->setPass()) break;

                if ($this->SetDeviceValue()) {
                    break;
                }
            }
        }
    }

    public function mainMenu()
    {
        if (!file_exists($this->home_dir)) {
            $this->home_dir = "connect.conf";
        }

        $status = '';
        while (true) {
            system("clear");
            $this->logo();
            if (isset($this->ip)) {
                $this->SetDeviceValue();
                print "\t{$this->green}Модель: {$this->red}{$this->getModel()}{$this->green}\tS/N: {$this->red}{$this->getSerialNumber()}{$this->green}{$this->nc}\n";
                print "\t{$this->green}IP: {$this->red}{$this->ip}{$this->green}\tAuth: {$this->red}{$this->user}{$this->green}:{$this->red}{$this->password}{$this->nc}\n";
                print "\t{$this->green}CPU: {$this->red}{$this->getCPU()}{$this->green} \t\tMemory Usage\\Available: {$this->red}{$this->getMemoryUsage()}\\{$this->getMemoryAvailable()}{$this->nc}\n";
                print "\t{$this->green}Uptime: {$this->red}{$this->getDeviceUpTime()}{$this->green}\tDevice Date: {$this->red}{$this->getDeviceTime()}{$this->nc}\n";
                print "\t{$this->green}Firmware Ver: {$this->red}{$this->getFirmwareVersion()}{$this->green}\tMac: {$this->red}{$this->getMacAddress()}{$this->nc}\n";

            }
            if (!empty($this->error)) {
                print "\n\n\t {$this->red} {$this->error} {$this->nc} \n";
            }
            print "\n {$this->green}Выберите действие:$this->nc";
            print "\n\n\t{$this->red}1)$this->nc Ввести ip камеры;";
            if (file_exists($this->home_dir))
                print "\n\t{$this->red}2)$this->nc Получить ip адреса из connect.conf;";
            if (!empty($this->ip)) {
                print "\n\t{$this->red}3)$this->nc Настроить битрейт;";
                print "\n\t{$this->red}4)$this->nc Сетевые настройки tcp/ip;";
                print "\n\t{$this->red}5)$this->nc Смена пароля {$this->green}{$this->password}{$this->nc} у {$this->green}{$this->user}{$this->nc} на {$this->green}{$this->ip}{$this->nc};";
                print "\n\t{$this->red}6)$this->nc Настройка даты и время на {$this->green}{$this->ip}{$this->nc};";

                print "\n\t{$this->red}r)$this->nc Перезагрузить камеру ip:{$this->green}{$this->ip}{$this->nc};";
            }
            print "\n\n\t{$this->red}q)$this->nc Выход;\n\n";
            $num = readline("\n\tВведите: ");
            switch ($num) {
                case  '1':
                    $this->logo();
                    if ($status = $this->inputIpAdress()) {
                        $this->setVideoConfig(); //установка логопасс и поверка его
                    }
                    break;
                case  '2':
                    $this->logo();
                    if ($cams = $this->read_connect()) {
                        if (!$this->listCams($cams)) {
                            $this->SetDeviceValue();
                            $this->mainMenu();
                            break;
                        }
                        if ($this->deviceInfo()) {
                            while (true) {
                                if ($this->setParametsVideo()) {
                                    $this->mainMenu();
                                    break;
                                }
                            }
                        }
                    }
                    break;
                case  '3':
                    $this->logo();
                    $this->menuVideoRate();

                case '4':
                    $this->logo();
                    $this->menuNetworkSettings();
                    break;
                case '5':
                    while (true) {
                        $this->logo();
                        $this->error = "";
                        if ($this->changePassword()) {
                            $this->mainMenu();
                            break;
                        }
                    }
                case '6':
                    while (true) {
                        $this->logo();
                        $this->menuNTP();
                        break;
                    }
                    break;
                case 'r':
                    $this->logo();
                    $this->rebootDevice();
                    print "\n\t{$this->red}Перезагрузили {$this->ip}...";
                    sleep(5);
                    unset($this->ip);
                    unset($this->user);
                    unset($this->password);
                    break;
                case 'q':
                    $this->logo();
                    print "\t ------------------------------------------------";
                    print "\n\t|  \t   $this->green Powered By Shamagin Roman $this->nc  \t|";
                    print "\n\t|\t $this->green Скрипт завершен, хорошего дня!!! $this->nc \t|";
                    print "\n\t ------------------------------------------------";
                    exit("\n\n");
                    break;
            }

            if ($status) break;
        }
    }

    private function menuNTP()
    {
        $this->changeNTP();


    }


    private function getISO8601($timeZone)
    {
        $timeZones = $this->listTimeZone();
        preg_match("/\(GMT([+-:\d]+)\)/i", $timeZones[$timeZone], $match);
        $data['GMT'] = $match[1];
        $data['timeZone'] = $timeZone;
        preg_match("/[1-9]+/", $data['GMT'], $match);
        $data['DiffHour'] = (int)$match[0] - 3;
        $data['date'] = date('Y-m-d\TH:i:s' . $data['GMT'], strtotime($data['DiffHour'] . ' hours'));
        return $data;
    }

    private function changeNTP()
    {
        $this->logo();
        print "\n\n\t{$this->green}Текущее время на GBOX $this->nc" . date("Y-m-d H:i:s") . $this->nc;
        $NTP = simplexml_load_string(shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/$this->apiUrl/System/time"));
        $tmp_timeMode = $NTP->timeMode;
        $tmp_localTime = $NTP->localTime;
        $tmp_timeZone = $NTP->timeZone;

        $tmp_date = $this->timeZone($tmp_localTime, $tmp_timeZone);
        print "\n\n\tКамера{$this->green} {$this->ip}{$this->nc}";
        print "\n\tРежим:{$this->green} $tmp_timeMode{$this->nc}";

        print "\n\t{$this->nc}Время: " . $this->green . $tmp_date['Date'];
        print "\n\t{$this->nc}Зона: " . $this->green . $tmp_date['NameZone'] . $this->nc;


        print "\n\n {$this->green}Выберите действие:$this->nc";
        print "\n\n\t{$this->red}m)$this->nc Выход в главное меню \n\n\t";

        $input = readline("Продолжить [Enter]");
        switch ($input) {
            case 'm':
                $this->mainMenu();
                break;
            case '':
                $NTP->timeMode = "manual";
                break;
            default:
                $this->changeNTP();
                break;
        }

        $NTP_Server = simplexml_load_string(shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/$this->apiUrl/System/time/"));

        $this->logo();
        $tz = $this->listTimeZone();
        $k = 1;
        foreach ($tz as $item => $value) {
            $key[$k] = $item;
            print "\n\t{$this->red}" . $k . ") " . $this->nc . $value;
            $k++;
        }
        $date = $this->timeZone($NTP_Server->localTime, $NTP_Server->timeZone);
        print "\n\n\t";
        $input = readline("Выберите часовой пояс: ");
        $input = (int)$input;
        if ($input <= count($tz)) {
            $input = $key[$input];
            $NTP_Server->timeZone = $input;
            $NTP_Server->timeMode = "manual";
            $NTP_Server->localTime = date('Y-m-d\TH:i:s' . $date['GMT'], strtotime($date['DiffHour'] . ' hours'));
            $NTP_Server->asXml('updated.xml');
            shell_exec("curl -s -X PUT -d @updated.xml http://{$this->user}:{$this->password}@{$this->ip}/$this->apiUrl/System/time/");
            unlink('updated.xml');
        }

    }

    private function networkSetAllGateway()
    {
        $this->logo();
        $cams = $this->read_connect();
        $tmp_ip = $this->ip;
        $tmp_user = $this->user;
        $tmp_pass = $this->password;
        foreach ($cams as $key => $cam) {
            $this->ip = $cam[0];
            $this->user = $cam[1];
            $this->password = $cam[2];
            $this->deviceInfo2();
            if ($this->ping($this->ip)) {
                $gateway = shell_exec("ifconfig | grep 192.168.137 | grep \"inet addr\" | cut -d ':' -f 2 | cut -d ' ' -f 1");

                $NetworkSettings = simplexml_load_string(shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/$this->apiUrl/System/Network/interfaces/1/ipAddress"));
                $NetworkSettings->addressingType = "static";
                $NetworkSettings->ipAddress = $this->ip;
                $NetworkSettings->subnetMask = "255.255.255.0";
                $NetworkSettings->DefaultGateway->ipAddress = $gateway;
                $NetworkSettings->PrimaryDNS->ipAddress = "8.8.8.8";
                $NetworkSettings->SecondaryDNS->ipAddress = "8.8.4.4";

                print "\n\t {$this->green}Камера " . ($key + 1) . ":{$this->nc}\n";
                print "\t Ip адрес: {$this->green}{$NetworkSettings->ipAddress}{$this->nc}\n";
                print "\t Маска: {$this->green}{$NetworkSettings->subnetMask}{$this->nc}\n";
                print "\t Шлюз: {$this->green}{$NetworkSettings->DefaultGateway->ipAddress}{$this->nc}\n";
                print "\t DNS1: {$this->green}{$NetworkSettings->PrimaryDNS->ipAddress}{$this->nc}\n";
                print "\t DNS2: {$this->green}{$NetworkSettings->SecondaryDNS->ipAddress}{$this->nc}\n";
                print "\t ------------------------------------------------\n";

                $NetworkSettings->asXml('updated.xml');
                shell_exec("curl -s -X PUT -d @updated.xml http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/System/Network/interfaces/1/ipAddress");
                $this->rebootDevice();
                unlink("updated.xml");
            } else {
                print "\n\t {$this->green}Камера " . ($key + 1) . " не пинг{$this->nc}\n";
            }
        }

        $this->ip = $tmp_ip;
        $this->user = $tmp_user;
        $this->password = $tmp_pass;
        readline("ГОТОВО!!! Нажми [ENTER]");
        $this->mainMenu();
    }

    private
    function networkSettings()
    {
        $this->logo();
        $gateway = shell_exec("ifconfig | grep 192.168.137 | grep \"inet addr\" | cut -d ':' -f 2 | cut -d ' ' -f 1");

        $NetworkSettings = simplexml_load_string(shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/$this->apiUrl/System/Network/interfaces/1/ipAddress"));
        $NetworkSettings->addressingType = "static";
        print "\n";
        while (1) {

            $input = readline("Новый IP [{$NetworkSettings->ipAddress}]: 192.168.137.");
            if (!empty($input)) {
                if ($input == 'm') {
                    return false;
                    break;
                }

                if ($this->validationIp("192.168.137." . $input)) {
                    $NetworkSettings->ipAddress = "192.168.137." . $input;
                    break;
                } else print  $this->error;
            } else break;
        }

        while (1) {
            $input = readline("Новый Шлюз [{$NetworkSettings->DefaultGateway->ipAddress}]: ", $gateway);
            if (!empty($input)) {
                if ($input == 'm') {
                    return false;
                    break;
                }

                if ($this->validationIp($input)) {
                    $NetworkSettings->DefaultGateway->ipAddress = $input;
                    break;
                } else print  $this->error;

            } else break;
        }

        $NetworkSettings->PrimaryDNS->ipAddress = "8.8.8.8";
        $NetworkSettings->SecondaryDNS->ipAddress = "8.8.4.4";

        print "\n\t {$this->green}Информация по сетевым настройкам:{$this->nc}\n";
        print "\t Ip адрес: {$this->green}{$NetworkSettings->ipAddress}{$this->nc}\n";
        print "\t Маска: {$this->green}{$NetworkSettings->subnetMask}{$this->nc}\n";
        print "\t Шлюз: {$this->green}{$NetworkSettings->DefaultGateway->ipAddress}{$this->nc}\n";
        print "\t DNS1: {$this->green}{$NetworkSettings->PrimaryDNS->ipAddress}{$this->nc}\n";
        print "\t DNS2: {$this->green}{$NetworkSettings->SecondaryDNS->ipAddress}{$this->nc}\n";
        print "\t ------------------------------------------------\n";

        while (1) {
            $status = false;
            $edit = readline('Сохранить? [y\n]: ');
            switch ($edit) {
                case 'y':
                    $NetworkSettings->asXml('updated.xml');
                    shell_exec("curl -s -X PUT -d @updated.xml http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/System/Network/interfaces/1/ipAddress");
                    $this->rebootDevice();
                    unlink("updated.xml");
                    unset($this->ip);
                    unset($this->user);
                    unset($this->password);
                    $status = true;
                    break;
                case 'n':
                    $status = true;
                    break;
            }
            if ($status) break;

        }
    }

    private function menuNetworkSettings()
    {
        while (true) {
            $this->logo();
            print "\n {$this->green}Выберите действие:$this->nc";
            print "\n\n\t{$this->red}1)$this->nc Настроить сеть на камере $this->ip;";
            print "\n\t{$this->red}2)$this->nc Прописать шлюз на всех камерах";
            print "\n\n\t{$this->red}m)$this->nc Выход в главное меню \n";
            $num = readline("\n\tВведите: ");
            switch ($num) {
                case 1:
                    $this->logo();
                    if (isset($this->ip)) {
                        $this->networkSettings();
                    }
                    break;
                case 2:
                    $this->networkSetAllGateway();
                    $this->mainMenu();
                    break;
                case 3:

                    break;
            }
            break;

        }
        $this->mainMenu();
    }

    private function menuVideoRate()
    {
        while (true) {
            $this->logo();
            print "\n {$this->green}Выберите действие:$this->nc";
            print "\n\n\t{$this->red}1)$this->nc Настроить битрейт на камере $this->ip;";
            print "\n\t{$this->red}2)$this->nc Настроить битрейт на всех камерах {$this->red}(Чтение из connect.conf) $this->nc";
            print "\n\t{$this->red}3)$this->nc Информация по всем камерам {$this->red}(Чтение из connect.conf) $this->nc";
            print "\n\n\t{$this->red}m)$this->nc Выход в главное меню \n";
            $num = readline("\n\tВведите: ");
            switch ($num) {
                case 1:
                    $this->logo();
                    if (isset($this->ip)) {
                        if ($this->deviceInfo()) {
                            while (true) {
                                if ($this->setParametsVideo()) {
                                    $this->mainMenu();
                                    break;
                                }

                            }
                        }
                    }
                    break;
                case 2:
                    $cams = $this->read_connect();
                    $tmp_ip = $this->ip;
                    $tmp_user = $this->user;
                    $tmp_pass = $this->password;
                    $tmp_apiUrl = $this->apiUrl;
                    foreach ($cams as $cam) {
                        $this->deviceInfo2();
                        $this->ip = $cam[0];
                        $this->user = $cam[1];
                        $this->password = $cam[2];

                        if ($this->deviceInfo()) {
                            while (true) {
                                if ($this->setParametsVideo()) {
                                    break;
                                }
                            }
                        }
                    }

                    $this->ip = $tmp_ip;
                    $this->user = $tmp_user;
                    $this->password = $tmp_pass;
                    $this->apiUrl = $tmp_apiUrl;

                    $this->mainMenu();
                    break;
                case 3:
                    $cams = $this->read_connect();
                    $tmp_ip = $this->ip;
                    $tmp_user = $this->user;
                    $tmp_pass = $this->password;
                    $tmp_apiUrl = $this->apiUrl;
                    foreach ($cams as $cam) {
                        $this->ip = $cam[0];
                        $this->user = $cam[1];
                        $this->password = $cam[2];
                        $output = shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/System/deviceInfo");
                        if (is_object($xml = simplexml_load_string($output))) {
                            if (isset($xml->model)) {
                                $this->xml = $xml;
                                $this->apiUrl = $this->getApiUrl();

                                $output = shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/Streaming/channels/1");
                                $this->videoParamets = simplexml_load_string($output);
                                $output2 = shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/Streaming/channels/2");
                                $this->videoParamets2 = simplexml_load_string($output2);


                                print "\t ------------------------------------------------\n";
                                print "\t {$this->red}Камера: {$this->ip}{$this->nc}\n";
                                print "\t {$this->green}Имя на камере: \t {$this->nc}{$this->nc}{$this->videoParamets->channelName}{$this->nc}\n";
                                print "\t {$this->green}Описание \t Основной \t Вторичный{$this->nc}\n";
                                print "\t {$this->green}Разрешение: \t {$this->nc}{$this->videoParamets->Video->videoResolutionWidth}x{$this->videoParamets->Video->videoResolutionHeight} \t {$this->videoParamets2->Video->videoResolutionWidth}x{$this->videoParamets2->Video->videoResolutionHeight}{$this->nc}\n";
                                print "\t {$this->green}Кадры:    \t{$this->nc}" . ($this->videoParamets->Video->maxFrameRate / 100) . "fps \t\t " . ($this->videoParamets2->Video->maxFrameRate / 100) . "fps{$this->nc}\n";
                                print "\t {$this->green}Битрейт:   \t{$this->nc}{$this->videoParamets->Video->constantBitRate}Kbps \t{$this->videoParamets2->Video->constantBitRate}Kbps{$this->nc}\n";
                                print "\t ------------------------------------------------\n";
                            }
                        } else {
                            print "\t ------------------------------------------------\n";
                            print "\t {$this->red}Камера: {$this->ip} не пинг...{$this->nc}\n";
                            print "\t ------------------------------------------------\n";
                        }
                    }
                    $this->ip = $tmp_ip;
                    $this->user = $tmp_user;
                    $this->password = $tmp_pass;
                    $this->apiUrl = $tmp_apiUrl;
                    readline("\n\t {$this->green} Нажмите [Enter] продолжить {$this->nc}");
                    break;
            }
            break;

        }
        $this->mainMenu();
    }

    private function validationIp($input)
    {
        if (filter_var(trim($input), FILTER_VALIDATE_IP)) {
            $this->error = "";
            return true;
        } else {
            $this->error = ("\t Ошибка в ip адресе:{$this->red} $input{$this->nc}, главное меню m. \n");
            return false;
        }
    }

    private
    function listCams($date)
    {
        $this->error = "";
        while (true) {
            $this->logo();
            print "\n {$this->green} Выберите камеру или [m] меню:{$this->nc} ";
            $array = array();
            foreach ($date as $k => $value) {
                if (isset($value[0], $value[1], $value[2]))
                    $array[] = $value[0] . ":" . $value[1] . ":" . $value[2];
            }
            $k = 0;
            foreach ($array as $value) {
                list($ip, $login, $pass) = explode(":", $value);
                $array[] = array($ip, $login, $pass);
                $k++;
                print "\n\t {$this->red} {$k}){$this->nc} {$ip} {$login}:{$pass}";
            }
            print "\n";
            echo $this->error;
            $input = readline("\n\n Номер камеры : ");
            if ($input == 'm') {
                return false;
                break;
            }
            if (is_numeric($input)) {
                if (isset($date[$input - 1])) {
                    $this->ip = $date[$input - 1][0];
                    $this->user = $date[$input - 1][1];
                    $this->password = $date[$input - 1][2];

                    if ($this->ping($this->ip)) {
                        break;
                    } else
                        echo $this->error;
                }
            }

        }
    }

    private
    function inputIpAdress()
    { //Отдельный метод по вводу ip
        print("\n");
        while (1) {
            $input = readline("\nВведите IP: ");
            if ($input == 'm') {
                return false;
                break;
            }
            if (!$this->valid_ip($input)) {
                print $this->error;
            } else {

                if ($this->ping($this->ip)) {
                    return true;
                    break;
                } else print $this->error;
            }
        }
    }

    private
    function valid_ip($ip) //метод валидации ip
    {
        if (!empty($ip)) {

            if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                $this->ip = $ip;
                $this->error = "";
                return true;
            } else {
                $this->error = ("\t Ошибка в ip адресе:{$this->red} $ip{$this->nc}, главное меню m. \n");
                return false;
            }
        } else {
            $this->error = "\t {$this->red} Ввели пустую строку, главное меню m. {$this->nc} \n";
            return false;
        }

    }

    private
    function ping($ip)
    {
        exec("ping -c1 -W1 {$ip}", $outcome, $status);
        if ($status == 0) {
            return true;
        } else {

            $this->error = "\n\t{$this->red} Не доступен хост $this->ip $this->nc\n\n";
            unset($this->ip);
            return false;
        }
    }

    private
    function setUser()
    {
        $this->user = 'admin';
    }

    private
    function setPass()
    {
        $this->password = '';
        print "\n Выберите стандартный пароль или введите другой:";
        print "\n\t 1) 12345";
        print "\n\t 2) admin12345";
        print "\n\t 3) tetraroot";
        print "\n\t 4) tetraroot12345 \n";
        print "\n\n\t m) Выход в главное меню \n";
        $input = readline("\n Введите : ");
        $this->logo();
        switch ($input) {
            case '1':
                $this->password = '12345';
                return true;
                break;
            case '2':
                $this->password = 'admin12345';
                return true;
                break;
            case '3':
                $this->password = 'tetraroot';
                return true;
                break;
            case '4':
                $this->password = 'tetraroot12345';
                return true;
                break;
            case 'm':
                $this->ip = NULL;
                $this->user = NULL;
                $this->password = NULL;
                return false;
                break;
            default:
                $this->password = urlencode(trim($input));
                return true;
                break;

        }
    }

    private function changePassword()
    {
        print "\n Установить следующий пароль или укажите свой";
        print "\n\t 1) admin12345";
        print "\n\t 2) tetraroot";
        print "\n\t 3) tetraroot12345 \n";
        print "\n\n\t m) Выход в главное меню \n";

        if (isset($this->error)) {
            print "\n\t {$this->red}{$this->error}{$this->nc}\n";
        }
        $input = readline("\n Введите : ");

        switch ($input) {
            case '1':
                $input = 'admin12345';
                break;
            case '2':
                $input = 'tetraroot';
                break;
            case '3':
                $input = 'tetraroot12345';
                break;
            case 'm':
                $input = $this->password;
                break;
        }

        if (empty($input)) {
            $this->error = "Ввели пустой пароль";
            return false;
        } elseif (strlen($input) < 8) {
            $this->error = "Пароль должен содержать минимум 8 символов";
            return false;
        } else {
            if ($input == $this->password) {
                return true;
            } else {

                unset($this->error);
                $tmp = simplexml_load_string(shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/$this->apiUrl/Security/users/1"));
                if (isset($tmp->userName)) {
                    $tmp->password = urlencode(trim($input));
                    $tmp->asXml('updated.xml');
                    $tmp = simplexml_load_string(shell_exec("curl -s -X PUT -d @updated.xml http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/Security/users/1"));
                    unlink("updated.xml");
                    if ($tmp->statusCode == 1) {
                        $this->password = urlencode(trim($input));
                        return true;
                        $this->mainMenu();
                    }
                }
            }
        }
    }

    private function SetDeviceValue()
    {
        $output = shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/System/deviceInfo");
        if (is_object($xml = simplexml_load_string($output))) {
            if (isset($xml->model)) {
                $this->xml = $xml;
                $this->apiUrl = $this->getApiUrl(); //Получаем ссылку
                $this->statusHardware = simplexml_load_string(shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/$this->apiUrl/System/status;"));
                return true;
            } else {
                $this->error = "Пароль указан неверно для $this->ip $this->user:" . urldecode($this->password);
                $this->DestroyAll();
                return false;
            }

        }
    }

    private function rebootDevice()
    {
        shell_exec("curl -s -X PUT http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/System/reboot");
    }


    private
    function deviceInfo()
    {
        $this->logo();
        $output = shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/System/deviceInfo");
        if (is_object($xml = simplexml_load_string($output))) {
            /*
            Камеры разные и здесь лучше не чудить с xml ответами, будем парсить поле модель, если есть
            объект->модель, значит все ок.
             */
            if (isset($xml->model)) {
                $this->xml = $xml;
                print "\t {$this->green}Успешное покдлючение к $this->ip... $this->nc \n\t user:$this->user\n\t password:$this->password \n";
                print "\t ------------------------------------------------\n";
                print "\t {$this->red}Информация по камере:{$this->nc}\n";
                print "\t ------------------------------------------------\n";
                $this->apiUrl = $this->getApiUrl(); //Получаем ссылку
                $this->statusHardware = simplexml_load_string(shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/$this->apiUrl/System/status;"));

                if ($this->getParametsVideo()) return true;

            } else {
                print "\t {$this->red} Пароль указан неверно для $this->ip $this->user:" . urldecode($this->password) . " $this->nc \n";
                return false;
            }
        } else {
            $i = 5;
            $point = "";
            while ($i >= 0) {
                $this->logo();
                print("\t {$this->red} Не удалось создать объект XML {$this->ip} $this->nc \n");
                $point .= ".";
                print("\t {$this->red} переход в главное меню через $i секунд{$point} $this->nc \n");
                if ($point == "...") $point = "";
                sleep(1);
                system("clear");
                $i--;
            }

//            unset($this->ip);
//            $this->mainMenu();
        }
    }

    function deviceInfo2()
    {
        $output = shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/System/deviceInfo");
        if (is_object($xml = simplexml_load_string($output))) {
            /*
            Камеры разные и здесь лучше не чудить с xml ответами, будем парсить поле модель, если есть
            объект->модель, значит все ок.
             */
            if (isset($xml->model)) {
                $this->xml = $xml;
                $this->apiUrl = $this->getApiUrl(); //Получаем ссылку

            }
        } else {
            print("\n\n\t {$this->red} Не удалось создать объект XML {$this->ip} $this->nc \n");
        }
    }

    private
    function getParametsVideo()
    {
        $output = shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/Streaming/channels/1");
        $this->videoParamets = simplexml_load_string($output);
        $output2 = shell_exec("curl -s http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/Streaming/channels/2");
        $this->videoParamets2 = simplexml_load_string($output2);
        print "\t Имя камеры: {$this->videoParamets->channelName}\n";
        print "\t ------------------------------------------------";
        print "\n\t {$this->green}Информация по видео: Главный поток:{$this->nc}\n";
        print "\t Текущее разрешение: {$this->videoParamets->Video->videoResolutionWidth} x {$this->videoParamets->Video->videoResolutionHeight}\n";
        print "\t Частота кадров: " . ($this->videoParamets->Video->maxFrameRate / 100) . "fps\n";
        print "\t Битрейт: {$this->videoParamets->Video->constantBitRate}Kbps\n";
        print "\t ------------------------------------------------\n";
        print "\t ------------------------------------------------";
        print "\n\t {$this->green}Информация по видео: Вторичный поток:{$this->nc}\n";
        print "\t Текущее разрешение: {$this->videoParamets2->Video->videoResolutionWidth} x {$this->videoParamets2->Video->videoResolutionHeight}\n";
        print "\t Частота кадров: " . ($this->videoParamets2->Video->maxFrameRate / 100) . "fps\n";
        print "\t Битрейт: {$this->videoParamets2->Video->constantBitRate}Kbps\n";
        print "\t ------------------------------------------------\n";

        while (1) {
            $edit = readline('Будем что то менять? [y\n]: ');
            switch ($edit) {
                case 'y':
                    return true;
                    break;
                case 'n':
                    return false;
                    break;
            }
        }
    }

    private
    function setParametsVideo()
    {
        while (true) {
            print "\n\t Нажми [Enter] - пропустить параметр";
            $tmp = readline("\n\t Имя камеры [{$this->videoParamets->channelName}]: ");
            if (!empty($tmp)) {
                $this->videoParamets->channelName = $tmp;
                $this->videoParamets2->channelName = $tmp;
                break;
            } else break;
        }

        while (true) {
            print "\t 1) 960P Узкий\n";
            print "\t 2) 720P Широкий\n";
            $tmp = readline("\t Разрешение Осн. поток [{$this->videoParamets->Video->videoResolutionWidth}x{$this->videoParamets->Video->videoResolutionHeight}]: ");
            if (!empty($tmp)) {
                $edit = false;
                switch ($tmp) {
                    case '1':
                        $this->videoParamets->Video->videoResolutionWidth = 1280;
                        $this->videoParamets->Video->videoResolutionHeight = 960;
                        $edit = true;
                        break;
                    case '2':
                        $this->videoParamets->Video->videoResolutionWidth = 1280;
                        $this->videoParamets->Video->videoResolutionHeight = 720;
                        $edit = true;
                        break;
                }
                if ($edit) break;
            } else break;

        }


        $this->videoParamets2->Video->videoResolutionWidth = 704;
        $this->videoParamets2->Video->videoResolutionHeight = 576;


        if ($this->apiUrl == 'PSIA') $this->videoParamets->Video->videoQualityControlType = "vbr";
        else $this->videoParamets->Video->videoQualityControlType = "VBR";


        while (true) {
            $tmp = readline("\t Частота кадров Осн. поток[" . ($this->videoParamets->Video->maxFrameRate / 100) . " fps]: ");
            if (!empty($tmp)) {
                $this->videoParamets->Video->maxFrameRate = ($tmp * 100);
                break;
            } else break;
        }

        while (true) {
            $tmp = readline("\t Частота кадров Втор. поток[" . ($this->videoParamets2->Video->maxFrameRate / 100) . " fps]: ");
            if (!empty($tmp)) {
                $this->videoParamets2->Video->maxFrameRate = ($tmp * 100);
                break;
            } else break;
        }

        while (true) {
            $tmp = readline("\t Битрейт Осн. поток[{$this->videoParamets->Video->constantBitRate}Kbps]: ");
            if (!empty($tmp)) {
                $this->videoParamets->Video->constantBitRate = $tmp;
                $this->videoParamets->Video->vbrUpperCap = $tmp;
                break;
            } else break;
        }


        while (true) {
            $tmp = readline("\t Битрейт Втор. поток[{$this->videoParamets2->Video->constantBitRate}Kbps]: ");
            if (!empty($tmp)) {
                $this->videoParamets2->Video->constantBitRate = $tmp;
                $this->videoParamets2->Video->vbrUpperCap = $tmp;
                break;
            } else break;
        }

        $this->videoParamets->Video->fixedQuality = 100;
        $this->videoParamets2->Video->fixedQuality = 100;

        if ($this->apiUrl == 'PSIA') {
            $this->videoParamets->Video->Extensions->selfExt->GovLength = 25;
            $this->videoParamets2->Video->Extensions->selfExt->GovLength = 25;
        } else {
            $this->videoParamets->Video->GovLength = 25;
            $this->videoParamets2->Video->GovLength = 25;
        }
        $this->logo();
        print "\t ------------------------------------------------";
        print "\n\t {$this->red}Информация по видео:{$this->nc}\n";
        print "\t Имя камеры: {$this->videoParamets->channelName}\n";
        print "\t Разрешение Осн. поток: {$this->videoParamets->Video->videoResolutionWidth} x {$this->videoParamets->Video->videoResolutionHeight}\n";
        print "\t Разрешение Втор. поток: {$this->videoParamets2->Video->videoResolutionWidth} x {$this->videoParamets2->Video->videoResolutionHeight}\n";
        print "\t Частота кадров Осн. поток: " . ($this->videoParamets->Video->maxFrameRate / 100) . "fps\n";
        print "\t Частота кадров Втор поток: " . ($this->videoParamets2->Video->maxFrameRate / 100) . "fps\n";
        print "\t Битрейт Осн. поток: {$this->videoParamets->Video->constantBitRate}Kbps\n";
        print "\t Битрейт Втор поток: {$this->videoParamets2->Video->constantBitRate}Kbps\n";
        print "\t ------------------------------------------------\n";

        while (1) {
            $status = false;
            $edit = readline('Сохранить параметры [y\n]: ');
            switch ($edit) {
                case 'y':
                    $this->videoParamets->asXml('updated.xml');
                    shell_exec("curl -s -X PUT -d @updated.xml http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/Streaming/channels/1");
                    $this->videoParamets2->asXml('updated.xml');
                    shell_exec("curl -s -X PUT -d @updated.xml http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/Streaming/channels/2");

                    $this->videoParamets2->Video->videoResolutionWidth = 640;
                    $this->videoParamets2->Video->videoResolutionHeight = 480;

                    $this->videoParamets2->asXml('updated.xml');
                    $output = shell_exec("curl -s -X PUT -d @updated.xml http://{$this->user}:{$this->password}@{$this->ip}/{$this->apiUrl}/Streaming/channels/2");


                    $xml = simplexml_load_string($output);
                    if ($xml->statusString == "OK") {
                        print "\t ------------------------------------------------\n";
                        print("\t {$this->green} Настройки камеры успешно обновлены {$this->nc}\n");
                        print "\t ------------------------------------------------\n";
                        sleep(2);
                    }
                    unlink('updated.xml');
                    unlink($this->videoParamets);
                    unlink($this->videoParamets2);
                    $status = true;
                    return true;
                    break;
                case 'n':
                    $status = true;
                    return true;
                    break;
            }
            if ($status) break;
        }
    }

    private
    function getApiUrl()
    {
        preg_match("/(\d)/", $this->xml->firmwareVersion, $match);
        if ($match[0] == 4) return "PSIA";
        else  return "ISAPI";
    }

    private
    function getModel()
    {
        return $this->xml->model;
    }

    private
    function getSerialNumber()
    {
        return $this->xml->serialNumber;
    }

    private
    function getMacAddress()
    {
        return $this->xml->macAddress;
    }

    private
    function getFirmwareVersion()
    {
        return $this->xml->firmwareVersion;
    }

    private
    function getDeviceTime()
    {
        $date = new DateTime($this->statusHardware->currentDeviceTime);
        return $date->format('Y-m-d H:i:s');
    }

    private
    function getDeviceUpTime()
    {
        $num = floatval($this->statusHardware->deviceUpTime);
        $secs = fmod($num, 60);
        $num = (int)($num / 60);
        $mins = $num % 60;
        $num = (int)($num / 60);
        $hours = $num % 24;
        $num = (int)($num / 24);
        $days = $num;

        return "$days days $hours:$mins:$secs";
    }

    private
    function getCPU()
    {
        return $this->statusHardware->CPUList->CPU->cpuUtilization . "%";
    }

    function getMemoryUsage()
    {
        return $this->statusHardware->MemoryList->Memory->memoryUsage . "Mb";
    }

    function getMemoryAvailable()
    {
        return number_format(($this->statusHardware->MemoryList->Memory->memoryAvailable / 1024), 0) . "Mb";
    }

    public
    function read_connect()
    {

        if (!file_exists($this->home_dir)) {
            print("\t {$this->red} Не удается прочитать connect.conf {$this->nc}\n");
            return false;
        } else {
            $read_file = shell_exec('cat ' . $this->home_dir . ' | grep camera[1-9]_stream= | awk -F "=" \'{print $2}\'');
            $array = explode("\n", $read_file);
            foreach ($array as $value) {
                if ($value) {
                    preg_match("/^rtsp:\/\/([\w\d]+):(.*)@(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/i", $value, $matches);
                    if (count($matches) == 4) {
                        $cams_array[] = array($matches[3], $matches[1], $matches[2]);
                    }
                }
            }

            $cams_array = array_unique($cams_array, SORT_REGULAR);
            return $cams_array;
        }
    }

    private function timeZone($date, $zone)
    {
        $timezone = array("CST+12:00:00" => "(GMT-12:00) Международная линия смены дат",
            "CST+11:00:00" => "(GMT-11:00) Мидуэй, Самоа",
            "CST+10:00:00" => "(GMT-10:00) Гавайи",
            "CST+9:00:00" => "(GMT-09:00) Аляска",
            "CST+8:00:00" => "(GMT-08:00) Тихоокеанское время (США и Канада)",
            "CST+7:00:00" => "(GMT-07:00) Mountain Time (US&Canada)",
            "CST+6:00:00" => "(GMT-06:00) Central Time (US&Canada)",
            "CST+5:00:00" => "(GMT-05:00) Eastern Time(US&Canada)",
            "CST+4:30:00" => "(GMT-04:30) Каракас",
            "CST+4:00:00" => "(GMT-04:00) Атлантическое время (США и Канада)",
            "CST+3:30:00" => "(GMT-03:30) Ньюфаундленд",
            "CST+3:00:00" => "(GMT-03:00) Georgetown",
            "CST+2:00:00" => "(GMT-02:00) Срединно-Атлантического",
            "CST+1:00:00" => "(GMT-01:00) Cape Verde Islands, Azores",
            "CST+0:00:00" => "(GMT+00:00) Дублин, Эдинбург, Лондон",
            "CST-1:00:00" => "(GMT+01:00) Амстердам, Берлин, Рим, Париж",
            "CST-2:00:00" => "(GMT+02:00) Афины, Иерусалим",
            "CST-3:00:00" => "(GMT+03:00) Санкт-Петербург",
            "CST-3:30:00" => "(GMT+03:30) Тегеран",
            "CST-4:00:00" => "(GMT+04:00) Caucasus Standard Time",
            "CST-4:30:00" => "(GMT+04:30) Кабул",
            "CST-5:00:00" => "(GMT+05:00) Екатеринбург",
            "CST-5:30:00" => "(GMT+05:30) Мадрас, Бомбей, Дели",
            "CST-5:45:00" => "(GMT+05:45) Катманду",
            "CST-6:00:00" => "(GMT+06:00) Алматы, Омск",
            "CST-6:30:00" => "(GMT+06:30) Рангун",
            "CST-7:00:00" => "(GMT+07:00) Красноярск, Новосибирск",
            "CST-8:00:00" => "(GMT+08:00) Иркутск",
            "CST-9:00:00" => "(GMT+09:00) Якутск",
            "CST-9:30:00" => "(GMT+09:30) Аделаида, Дарвин",
            "CST-10:00:00" => "(GMT+10:00) Владивосток",
            "CST-11:00:00" => "(GMT+11:00) Магадан, Сахалин, Камчатка",
            "CST-12:00:00" => "(GMT+12:00) Окленд, Веллингтон, Фиджи",
            "CST-13:00:00" => "(GMT+13:00) Нукуалофа");

        $result = array();
        $result["NameZone"] = $timezone[(string)$zone];
        preg_match("/\(GMT([+_\d:]+)\)/", $result['NameZone'], $output_array);
        $result['GMT'] = $output_array[1];
        $result['Date'] = preg_replace("/([\d-]+)T([\d:]+)\+[\d:]+/", "$1 $2", $date);
        preg_match("/[1-9]+/", $result['GMT'], $match);
        $result['DiffHour'] = (int)$match[0] - 3;
        return $result;

    }

    private function listTimeZone()
    {
        return $timezone = array(
            "CST-1:00:00" => "(GMT+01:00) Амстердам, Берлин, Рим, Париж",
            "CST-2:00:00" => "(GMT+02:00) Афины, Иерусалим",
            "CST-3:00:00" => "(GMT+03:00) Санкт-Петербург",
            "CST-4:00:00" => "(GMT+04:00) Саратов",
            "CST-5:00:00" => "(GMT+05:00) Оренбург, Новый Уренгой",
            "CST-6:00:00" => "(GMT+06:00) Алматы, Омск",
            "CST-7:00:00" => "(GMT+07:00) Новосибирск, Томск",
            "CST-8:00:00" => "(GMT+08:00) Иркутск",
            "CST-9:00:00" => "(GMT+09:00) Якутск",
            "CST-10:00:00" => "(GMT+10:00) Владивосток",
            "CST-11:00:00" => "(GMT+11:00) Магадан, Сахалин, Камчатка",
            "CST-12:00:00" => "(GMT+12:00) Окленд, Веллингтон, Фиджи",
            "CST-13:00:00" => "(GMT+13:00) Нукуалофа");
    }



}

$hik = new hik();

while (true) {
    $hik->mainMenu();


}
